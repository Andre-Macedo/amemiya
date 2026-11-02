import sys
import json
import urllib.request
import urllib.error
import subprocess

# Ensure UTF-8 encoding for stdin, stdout and stderr on Windows
if hasattr(sys.stdin, "reconfigure"):
    sys.stdin.reconfigure(encoding="utf-8")
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8")

active_port = 27182
session_id = None

def log(msg):
    try:
        sys.stderr.write(f"[fusion_bridge] {msg}\n")
        sys.stderr.flush()
    except Exception:
        pass

def find_working_port():
    global active_port
    candidate_ports = [27182, 63692]
    try:
        cmd = "Get-NetTCPConnection -OwningProcess (Get-Process Fusion360 -ErrorAction SilentlyContinue).Id -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty LocalPort"
        out = subprocess.check_output(["powershell", "-NoProfile", "-Command", cmd], text=True, timeout=5)
        for line in out.strip().splitlines():
            line = line.strip()
            if line.isdigit():
                p = int(line)
                if p not in candidate_ports:
                    candidate_ports.append(p)
    except Exception as e:
        log(f"Process port scan failed: {e}")

    for p in candidate_ports:
        url = f"http://127.0.0.1:{p}/mcp"
        init_data = json.dumps({
            "jsonrpc": "2.0",
            "id": "probe",
            "method": "initialize",
            "params": {
                "protocolVersion": "2024-11-05",
                "capabilities": {},
                "clientInfo": {"name": "fusion-bridge-probe", "version": "1.0"}
            }
        }).encode("utf-8")
        req = urllib.request.Request(url, data=init_data, headers={"Content-Type": "application/json"})
        try:
            with urllib.request.urlopen(req, timeout=1.5) as resp:
                if resp.status == 200:
                    active_port = p
                    log(f"Discovered working Fusion MCP port: {p}")
                    return p
        except Exception:
            continue
    log(f"Fallback to default port: {active_port}")
    return active_port

def get_url():
    return f"http://127.0.0.1:{active_port}/mcp"

def ensure_session():
    global session_id
    try:
        url = get_url()
        init_data = json.dumps({
            "jsonrpc": "2.0",
            "id": "bridge-init",
            "method": "initialize",
            "params": {
                "protocolVersion": "2024-11-05",
                "capabilities": {},
                "clientInfo": {"name": "fusion-bridge", "version": "1.0"}
            }
        }).encode("utf-8")
        req = urllib.request.Request(url, data=init_data, headers={"Content-Type": "application/json"})
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                session_id = resp.headers.get("MCP-Session-Id")
        except Exception:
            find_working_port()
            url = get_url()
            req = urllib.request.Request(url, data=init_data, headers={"Content-Type": "application/json"})
            with urllib.request.urlopen(req, timeout=10) as resp:
                session_id = resp.headers.get("MCP-Session-Id")
        
        notif_data = json.dumps({
            "jsonrpc": "2.0",
            "method": "notifications/initialized"
        }).encode("utf-8")
        req_notif = urllib.request.Request(url, data=notif_data, headers={"Content-Type": "application/json", "MCP-Session-Id": session_id})
        with urllib.request.urlopen(req_notif, timeout=10) as resp:
            pass
        log(f"Auto-initialized fresh session: {session_id} on port {active_port}")
    except Exception as e:
        log(f"Error initializing session: {e}")

def execute_http_request(req, retry_on_session_error=True):
    global session_id

    # If initialize request from client, do standard pass-through
    if req.get("method") == "initialize":
        headers = {"Content-Type": "application/json"}
        data = json.dumps(req).encode("utf-8")
        http_req = urllib.request.Request(get_url(), data=data, headers=headers)
        try:
            with urllib.request.urlopen(http_req, timeout=120) as resp:
                new_session = resp.headers.get("MCP-Session-Id")
                if new_session:
                    session_id = new_session
                return resp.read().decode("utf-8")
        except Exception:
            find_working_port()
            http_req = urllib.request.Request(get_url(), data=data, headers=headers)
            with urllib.request.urlopen(http_req, timeout=120) as resp:
                new_session = resp.headers.get("MCP-Session-Id")
                if new_session:
                    session_id = new_session
                return resp.read().decode("utf-8")

    if not session_id:
        ensure_session()

    headers = {"Content-Type": "application/json"}
    if session_id:
        headers["MCP-Session-Id"] = session_id

    data = json.dumps(req).encode("utf-8")
    http_req = urllib.request.Request(get_url(), data=data, headers=headers)

    try:
        with urllib.request.urlopen(http_req, timeout=120) as resp:
            new_session = resp.headers.get("MCP-Session-Id")
            if new_session:
                session_id = new_session
            content = resp.read().decode("utf-8")
            if content and retry_on_session_error:
                try:
                    parsed = json.loads(content)
                    if "error" in parsed and ("session" in str(parsed["error"]).lower() or parsed["error"].get("code") == -32600):
                        log(f"Session error in response: {parsed['error']}, retrying with fresh session...")
                        ensure_session()
                        return execute_http_request(req, retry_on_session_error=False)
                except Exception:
                    pass
            return content
    except urllib.error.HTTPError as e:
        err_body = e.read().decode("utf-8")
        if (e.code in (401, 403, 404) or "session" in err_body.lower()) and retry_on_session_error:
            log(f"HTTP {e.code} ({err_body}), refreshing session and retrying...")
            ensure_session()
            return execute_http_request(req, retry_on_session_error=False)
        raise e
    except Exception as e:
        if retry_on_session_error:
            log(f"Connection error ({e}), re-scanning port...")
            find_working_port()
            ensure_session()
            return execute_http_request(req, retry_on_session_error=False)
        raise e

def main():
    log("Fusion MCP stdio bridge started")
    while True:
        line = sys.stdin.readline()
        if not line:
            break
        line = line.strip()
        if not line:
            continue
        try:
            req = json.loads(line)
        except Exception as e:
            log(f"JSON parse error: {e}")
            continue

        try:
            content = execute_http_request(req)
            if content and "id" in req:
                sys.stdout.write(content.strip() + "\n")
                sys.stdout.flush()
        except urllib.error.HTTPError as e:
            err_body = e.read().decode("utf-8")
            log(f"HTTP Error {e.code}: {err_body}")
            if "id" in req:
                err_resp = {
                    "jsonrpc": "2.0",
                    "id": req["id"],
                    "error": {"code": -32603, "message": f"HTTP {e.code}: {err_body}"}
                }
                sys.stdout.write(json.dumps(err_resp) + "\n")
                sys.stdout.flush()
        except Exception as e:
            log(f"Request error: {e}")
            if "id" in req:
                err_resp = {
                    "jsonrpc": "2.0",
                    "id": req["id"],
                    "error": {"code": -32603, "message": str(e)}
                }
                sys.stdout.write(json.dumps(err_resp) + "\n")
                sys.stdout.flush()

if __name__ == "__main__":
    main()
