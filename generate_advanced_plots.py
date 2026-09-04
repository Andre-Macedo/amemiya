import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import numpy as np
import os

# 1. Carregar dados
csv_path = 'storage/app/public/artigo_DUMP_TOTAL.csv'
df = pd.read_csv(csv_path)
df['status_clean'] = df['status'].replace({'normal': 'saudavel'})
# Filtrar apenas estados ativos para não poluir os gráficos de falha
df_active = df[df['status_clean'] != 'desligada'].copy()

output_dir = 'storage/app/public/graficos_artigo_avancados'
os.makedirs(output_dir, exist_ok=True)

plt.style.use('bmh')
colors = {'saudavel': '#2ecc71', 'desbalanceamento': '#e74c3c'}

print("Gerando gráficos avançados...")

# ---------------------------------------------------------
# 5. ASSINATURA DE ENERGIA MULTIAXIAL (Fingerprint)
# ---------------------------------------------------------
plt.figure(figsize=(14, 8))
bands = ['x_energy_band_1', 'x_energy_band_2', 'x_energy_band_3', 'x_energy_band_4',
         'y_energy_band_1', 'y_energy_band_2', 'y_energy_band_3', 'y_energy_band_4',
         'z_energy_band_1', 'z_energy_band_2', 'z_energy_band_3', 'z_energy_band_4']

df_sig = df_active.groupby('status_clean')[bands].mean().stack().reset_index()
df_sig.columns = ['Status', 'Feature', 'Energia']

sns.barplot(x='Feature', y='Energia', hue='Status', data=df_sig, palette=colors)
plt.title('Assinatura Digital de Energia: Comparação Multiaxial (X, Y, Z)', fontsize=14)
plt.xticks(rotation=45, ha='right')
plt.ylabel('Energia Média Acumulada')
plt.grid(axis='y', linestyle='--', alpha=0.7)
plt.tight_layout()
plt.savefig(f'{output_dir}/05_assinatura_energia_multiaxial.png', dpi=300)
print("- Gerado: 05_assinatura_energia_multiaxial.png")

# ---------------------------------------------------------
# 6. MAPA DE DENSIDADE (CLUSTERS IA)
# ---------------------------------------------------------
g = sns.jointplot(data=df_active, x='rms_global', y='mic_rms', hue='status_clean', 
                  kind='kde', palette=colors, fill=True, alpha=0.5)
g.fig.suptitle('Separação de Estados no Espaço Multimodal (Vibração vs Som)', fontsize=14)
g.fig.tight_layout()
g.fig.subplots_adjust(top=0.9)
plt.savefig(f'{output_dir}/06_densidade_multimodal.png', dpi=300)
print("- Gerado: 06_densidade_multimodal.png")

# ---------------------------------------------------------
# 7. ANÁLISE DE IMPACTO (KURTOSIS POR EIXO)
# ---------------------------------------------------------
kurt_cols = ['x_kurtosis', 'y_kurtosis', 'z_kurtosis']
df_kurt = df_active.groupby('status_clean')[kurt_cols].mean().reset_index()
df_kurt = pd.melt(df_kurt, id_vars=['status_clean'], value_vars=kurt_cols)

plt.figure(figsize=(10, 6))
sns.barplot(x='variable', y='value', hue='status_clean', data=df_kurt, palette=colors)
plt.title('Nível de Impacto (Kurtosis) - Sensibilidade aos Picos de Falha', fontsize=14)
plt.ylabel('Valor de Curtose')
plt.xlabel('Eixo de Medição')
plt.tight_layout()
plt.savefig(f'{output_dir}/07_impacto_kurtosis.png', dpi=300)
print("- Gerado: 07_impacto_kurtosis.png")

# ---------------------------------------------------------
# 8. ANÁLISE ACÚSTICA POR FREQUÊNCIA
# ---------------------------------------------------------
mic_bands = ['mic_energy_0_500', 'mic_energy_500_2000', 'mic_energy_2000_5000', 'mic_energy_5000_10000']
df_mic = df_active.groupby('status_clean')[mic_bands].mean().stack().reset_index()
df_mic.columns = ['Status', 'Faixa', 'Energia']

plt.figure(figsize=(12, 6))
sns.lineplot(x='Faixa', y='Energia', hue='Status', data=df_mic, marker='o', palette=colors, linewidth=3)
plt.title('Espectro Acústico Simplificado (Microfone INMP441)', fontsize=14)
plt.ylabel('Energia Acústica')
plt.yscale('log')
plt.xticks(range(4), ['0-500Hz', '500-2kHz', '2k-5kHz', '5k-10kHz'])
plt.grid(True, which="both", ls="-", alpha=0.2)
plt.tight_layout()
plt.savefig(f'{output_dir}/08_espectro_acustico.png', dpi=300)
print("- Gerado: 08_espectro_acustico.png")

print(f"\nSucesso! Gráficos de análise profunda salvos em {output_dir}")
