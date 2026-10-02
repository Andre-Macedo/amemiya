import sys
import json
import urllib.request
import urllib.error

# Ensure UTF-8 encoding for stdin, stdout and stderr on Windows
if hasattr(sys.stdin, "reconfigure"):
    sys.stdin.reconfigure(encoding="utf-8")
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8")

FUSION_MCP_URL = "http://127.0.0.1:27182/mcp"
session_id = None

def log(msg):
    try:
        sys.stderr.write(f"[fusion_bridge] {msg}\n")
        sys.stderr.flush()
    except Exception:
        pass

def main():
    global session_id
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

        headers = {"Content-Type": "application/json"}
        if session_id:
            headers["MCP-Session-Id"] = session_id

        data = json.dumps(req).encode("utf-8")
        http_req = urllib.request.Request(FUSION_MCP_URL, data=data, headers=headers)
        
        try:
            with urllib.request.urlopen(http_req, timeout=120) as resp:
                new_session = resp.headers.get("MCP-Session-Id")
                if new_session:
                    session_id = new_session
                content = resp.read().decode("utf-8")
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
