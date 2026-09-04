import pandas as pd
import matplotlib.pyplot as plt
import numpy as np
import os

# 1. Carregar dados
csv_path = 'storage/app/public/artigo_completo_36_features.csv'
if not os.path.exists(csv_path):
    print(f"Erro: Arquivo {csv_path} não encontrado.")
    exit()

df = pd.read_csv(csv_path)

# Criar diretório para as imagens
output_dir = 'storage/app/public/graficos_artigo'
os.makedirs(output_dir, exist_ok=True)

print("Iniciando geração de gráficos...")

# CONFIGURAÇÃO DE ESTILO
plt.style.use('bmh')
color_healthy = '#2ecc71' # Verde
color_fault = '#e74c3c'   # Vermelho

# ---------------------------------------------------------
# GRÁFICO 1: Comparação de RMS Global (Intensidade Total)
# ---------------------------------------------------------
plt.figure(figsize=(10, 6))
avg_rms = df.groupby('status')['rms_global'].mean()
std_rms = df.groupby('status')['rms_global'].std()

avg_rms.plot(kind='bar', yerr=std_rms, color=[color_fault, color_healthy], capsize=10)
plt.title('Vibração Média (RMS Global) - Saudável vs Desbalanceado', fontsize=14)
plt.ylabel('Aceleração (g)', fontsize=12)
plt.xlabel('Estado do Motor', fontsize=12)
plt.xticks(rotation=0)
plt.tight_layout()
plt.savefig(f'{output_dir}/01_comparacao_rms_global.png', dpi=300)
print("- Gerado: 01_comparacao_rms_global.png")

# ---------------------------------------------------------
# GRÁFICO 2: Espectro de Energia (Z-Axis Bands)
# ---------------------------------------------------------
# Vamos pegar as bandas do eixo Z como exemplo (onde o desbal costuma ser maior)
z_bands = ['z_energy_band_1', 'z_energy_band_2', 'z_energy_band_3', 'z_energy_band_4']
band_labels = ['Banda 1 (0-10Hz)', 'Banda 2 (10-50Hz)', 'Banda 3 (50-200Hz)', 'Banda 4 (200Hz+)']

df_grouped = df.groupby('status')[z_bands].mean().reset_index()

plt.figure(figsize=(12, 6))
bar_width = 0.35
index = np.arange(len(z_bands))

plt.bar(index, df_grouped[df_grouped['status'] == 'saudavel'][z_bands].values[0], 
        bar_width, label='Saudável', color=color_healthy)
plt.bar(index + bar_width, df_grouped[df_grouped['status'] == 'desbalanceamento'][z_bands].values[0], 
        bar_width, label='Desbalanceado', color=color_fault)

plt.xlabel('Bandas de Frequência', fontsize=12)
plt.ylabel('Energia Acumulada', fontsize=12)
plt.title('Distribuição de Energia por Frequência (Eixo Z)', fontsize=14)
plt.xticks(index + bar_width / 2, band_labels)
plt.legend()
plt.tight_layout()
plt.savefig(f'{output_dir}/02_espectro_energia_z.png', dpi=300)
print("- Gerado: 02_espectro_energia_z.png")

# ---------------------------------------------------------
# GRÁFICO 3: Correlação Vibração vs Ruído (Scatter Plot)
# ---------------------------------------------------------
plt.figure(figsize=(10, 6))
for status, group in df.groupby('status'):
    color = color_healthy if status in ['saudavel', 'normal'] else color_fault
    plt.scatter(group['rms_global'], group['mic_rms'], label=status, alpha=0.6, edgecolors='w', s=100, color=color)

plt.title('Assinatura Multimodal: Vibração vs Acústica', fontsize=14)
plt.xlabel('Vibração RMS Global (g)', fontsize=12)
plt.ylabel('Ruído Microfone (RMS)', fontsize=12)
plt.legend()
plt.grid(True, linestyle='--', alpha=0.7)
plt.tight_layout()
plt.savefig(f'{output_dir}/03_correlacao_vibracao_ruido.png', dpi=300)
print("- Gerado: 03_correlacao_vibracao_ruido.png")

# ---------------------------------------------------------
# GRÁFICO 4: Kurtosis (Picos de Impacto)
# ---------------------------------------------------------
# Kurtosis indica "choques" ou falhas pontuais
kurt_cols = ['x_kurtosis', 'y_kurtosis', 'z_kurtosis']
avg_kurt = df.groupby('status')[kurt_cols].mean()

plt.figure(figsize=(10, 6))
avg_kurt.plot(kind='bar', color=['#3498db', '#9b59b6', '#f1c40f'], ax=plt.gca())
plt.title('Análise de Curtose (Kurtosis) por Eixo', fontsize=14)
plt.ylabel('Valor de Curtose', fontsize=12)
plt.xlabel('Estado', fontsize=12)
plt.xticks(rotation=0)
plt.legend(['Eixo X', 'Eixo Y', 'Eixo Z'])
plt.tight_layout()
plt.savefig(f'{output_dir}/04_analise_curtose.png', dpi=300)
print("- Gerado: 04_analise_curtose.png")

print("\nSucesso! Todos os gráficos foram salvos em storage/app/public/graficos_artigo/")
