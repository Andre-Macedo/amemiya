#!/usr/bin/env sh
set -eu

CERT_DIR="$(cd "$(dirname "$0")" && pwd)/certs"
DOMAIN="${1:-mqtt.leantech.local}"

mkdir -p "$CERT_DIR"

echo "Gerando CA e certificado de servidor TLS (SAN: $DOMAIN) em $CERT_DIR..."

openssl genrsa -out "$CERT_DIR/ca.key" 4096
openssl req -x509 -new -nodes -key "$CERT_DIR/ca.key" -sha256 -days 1825 \
  -subj "/C=BR/O=LeanTech Metrologia/CN=Amemiya IoT Root CA" \
  -out "$CERT_DIR/ca.crt"

cat > "$CERT_DIR/san.cnf" <<EOF
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req
prompt = no

[req_distinguished_name]
CN = ${DOMAIN}

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = ${DOMAIN}
DNS.2 = mqtt
DNS.3 = amemiya-mqtt
DNS.4 = localhost
IP.1 = 127.0.0.1
EOF

openssl genrsa -out "$CERT_DIR/server.key" 2048
openssl req -new -key "$CERT_DIR/server.key" -out "$CERT_DIR/server.csr" -config "$CERT_DIR/san.cnf"
openssl x509 -req -in "$CERT_DIR/server.csr" \
  -CA "$CERT_DIR/ca.crt" -CAkey "$CERT_DIR/ca.key" -CAcreateserial \
  -out "$CERT_DIR/server.crt" -days 825 -sha256 \
  -extensions v3_req -extfile "$CERT_DIR/san.cnf"

rm -f "$CERT_DIR/server.csr" "$CERT_DIR/san.cnf"
chmod 600 "$CERT_DIR/ca.key" "$CERT_DIR/server.key"
chmod 644 "$CERT_DIR/ca.crt" "$CERT_DIR/server.crt"

echo "Certificados MQTTS gerados com sucesso."
