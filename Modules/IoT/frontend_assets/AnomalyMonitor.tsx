"use client";

import React, { useEffect, useState } from "react";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

interface AnomalyData {
  gateway_id: string;
  status: string;
  confidence: number;
  spectrogram_b64: string | null;
  detected_at: string;
}

export default function AnomalyMonitor({ tenantId }: { tenantId: string }) {
  const [anomaly, setAnomaly] = useState<AnomalyData | null>(null);
  const [showAlert, setShowAlert] = useState(false);

  useEffect(() => {
    // Configuração do Echo para o Reverb
    // Certifique-se de que as variáveis de ambiente estão corretas no Next.js
    const echo = new Echo({
      broadcaster: "reverb",
      key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
      wsPort: process.env.NEXT_PUBLIC_REVERB_PORT ?? 80,
      wssPort: process.env.NEXT_PUBLIC_REVERB_PORT ?? 443,
      forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? "https") === "https",
      enabledTransports: ["ws", "wss"],
    });

    const channel = echo.channel(`tenant.${tenantId}.iot`);

    channel.listen(".anomaly.detected", (data: AnomalyData) => {
      console.log("Anomalia Detectada:", data);
      setAnomaly(data);
      setShowAlert(true);
      
      // Auto-hide alert after 30 seconds
      setTimeout(() => setShowAlert(false), 30000);
    });

    return () => {
      echo.disconnect();
    };
  }, [tenantId]);

  if (!showAlert || !anomaly) return null;

  return (
    <div className="fixed bottom-4 right-4 z-50 w-96 bg-red-600 text-white rounded-lg shadow-2xl p-4 border-2 border-white animate-bounce">
      <div className="flex justify-between items-start">
        <h3 className="font-bold text-lg mb-2">🚨 ALERTA CRÍTICO: {anomaly.status.toUpperCase()}</h3>
        <button onClick={() => setShowAlert(false)} className="text-white hover:text-gray-200">
          ✕
        </button>
      </div>
      
      <p className="text-sm mb-4">
        Detectado em: {new Date(anomaly.detected_at).toLocaleString()}<br />
        Confiança da IA: <strong>{(anomaly.confidence * 100).toFixed(2)}%</strong>
      </p>

      {anomaly.spectrogram_b64 && (
        <div className="bg-black rounded p-1">
          <p className="text-[10px] text-gray-400 mb-1">Espectrograma Mel (Vibração Radial)</p>
          <img 
            src={`data:image/png;base64,${anomaly.spectrogram_b64}`} 
            alt="Espectrograma de Falha" 
            className="w-full h-32 object-cover rounded shadow-inner"
          />
        </div>
      )}

      <div className="mt-4 flex gap-2">
        <button className="flex-1 bg-white text-red-600 font-bold py-1 rounded text-sm hover:bg-gray-100">
          Ver Máquina
        </button>
        <button className="flex-1 border border-white text-white font-bold py-1 rounded text-sm hover:bg-red-700">
          Ignorar
        </button>
      </div>
    </div>
  );
}
