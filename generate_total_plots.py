import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import numpy as np
import os

# 1. Carregar dados
csv_path = 'storage/app/public/artigo_DUMP_TOTAL.csv'
if not os.path.exists(csv_path):
    print(f"Erro: Arquivo {csv_path} não encontrado.")
    exit()

df = pd.read_csv(csv_path)

# Padronizar status (agrupar normal e saudavel)
df['status_clean'] = df['status'].replace({'normal': 'saudavel'})

# Criar diretório para as imagens
output_dir = 'storage/app/public/graficos_artigo_total'
os.makedirs(output_dir, exist_ok=True)

print(f"Iniciando processamento de {len(df)} registros...")

# CONFIGURAÇÃO DE ESTILO
plt.style.use('bmh')
color_map = {'saudavel': '#2ecc71', 'desbalanceamento': '#e74c3c', 'desligada': '#95a5a6'}

# ---------------------------------------------------------
# GRÁFICO 1: Boxplot de RMS Global (Distribuição e Outliers)
# ---------------------------------------------------------
plt.figure(figsize=(12, 7))
sns.boxplot(x='status_clean', y='rms_global', data=df, palette=color_map)
plt.title('Distribuição da Vibração (RMS Global) - Boxplot Acadêmico', fontsize=14)
plt.ylabel('Aceleração (g)', fontsize=12)
plt.xlabel('Estado do Ativo', fontsize=12)
plt.tight_layout()
plt.savefig(f'{output_dir}/01_boxplot_vibracao_total.png', dpi=300)
print("- Gerado: 01_boxplot_vibracao_total.png")

# ---------------------------------------------------------
# GRÁFICO 2: Série Temporal de RMS Global (Tendência)
# ---------------------------------------------------------
plt.figure(figsize=(15, 6))
df['timestamp'] = pd.to_datetime(df['timestamp'])
df_sorted = df.sort_values('timestamp')

for status, group in df_sorted.groupby('status_clean'):
    plt.plot(group['timestamp'], group['rms_global'], label=status, marker='o', markersize=2, linestyle='', color=color_map.get(status, '#000'))

plt.title('Histórico de Monitoramento - Evolução Temporal da Vibração', fontsize=14)
plt.ylabel('RMS Global (g)', fontsize=12)
plt.xlabel('Tempo', fontsize=12)
plt.legend()
plt.grid(True, alpha=0.3)
plt.tight_layout()
plt.savefig(f'{output_dir}/02_serie_temporal_total.png', dpi=300)
print("- Gerado: 02_serie_temporal_total.png")

# ---------------------------------------------------------
# GRÁFICO 3: Mapa de Calor de Correlação (Top 10 Features)
# ---------------------------------------------------------
plt.figure(figsize=(12, 10))
# Seleciona features numéricas
numeric_df = df.select_dtypes(include=[np.number])
# Calcula correlação com o RMS Global
top_corr = numeric_df.corr()['rms_global'].abs().sort_values(ascending=False).head(15).index
sns.heatmap(numeric_df[top_corr].corr(), annot=True, cmap='RdYlGn', fmt=".2f")
plt.title('Matriz de Correlação - Top 15 Variáveis', fontsize=14)
plt.tight_layout()
plt.savefig(f'{output_dir}/03_heatmap_correlacao.png', dpi=300)
print("- Gerado: 03_heatmap_correlacao.png")

# ---------------------------------------------------------
# GRÁFICO 4: Comparação de Bandas de Energia (Violin Plot)
# ---------------------------------------------------------
plt.figure(figsize=(12, 7))
z_band = 'z_energy_band_1'
sns.violinplot(x='status_clean', y=z_band, data=df[df['status_clean'] != 'desligada'], palette=color_map)
plt.title(f'Densidade de Energia na {z_band} (Assinatura de Falha)', fontsize=14)
plt.ylabel('Energia', fontsize=12)
plt.yscale('log') # Escala logarítmica para ver pequenas variações
plt.tight_layout()
plt.savefig(f'{output_dir}/04_violin_energia_banda1.png', dpi=300)
print("- Gerado: 04_violin_energia_banda1.png")

print(f"\nSucesso! Gráficos baseados em {len(df)} amostras salvos em {output_dir}")
