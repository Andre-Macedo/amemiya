import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import numpy as np
import os

# 1. Carregar dados
csv_path = 'storage/app/public/artigo_DUMP_TOTAL.csv'
df = pd.read_csv(csv_path)
df['status_clean'] = df['status'].replace({'normal': 'saudavel'})
df_active = df[df['status_clean'] != 'desligada'].copy()

output_dir = 'storage/app/public/graficos_artigo_finais'
os.makedirs(output_dir, exist_ok=True)

plt.style.use('bmh')
colors = {'saudavel': '#2ecc71', 'desbalanceamento': '#e74c3c'}

print("Gerando gráficos finais e simplificados...")

# ---------------------------------------------------------
# 09. RMS GLOBAL SIMPLES (O Clássico)
# ---------------------------------------------------------
plt.figure(figsize=(8, 6))
avg_rms = df_active.groupby('status_clean')['rms_global'].mean()
std_rms = df_active.groupby('status_clean')['rms_global'].std()

avg_rms.plot(kind='bar', yerr=std_rms, color=['#e74c3c', '#2ecc71'], capsize=10, alpha=0.8)
plt.title('Comparação de Intensidade Vibracional Média', fontsize=14)
plt.ylabel('Aceleração RMS Global (g)')
plt.xlabel('Estado do Motor')
plt.xticks(rotation=0)
plt.grid(axis='y', linestyle='--', alpha=0.5)
plt.tight_layout()
plt.savefig(f'{output_dir}/09_rms_global_simples.png', dpi=300)

# ---------------------------------------------------------
# 10. RMS POR EIXO (X, Y, Z side-by-side)
# ---------------------------------------------------------
plt.figure(figsize=(10, 6))
axes_rms = ['rms_x', 'rms_y', 'rms_z']
df_axes = df_active.groupby('status_clean')[axes_rms].mean().reset_index()
df_axes_melted = pd.melt(df_axes, id_vars=['status_clean'], value_vars=axes_rms)

sns.barplot(x='variable', y='value', hue='status_clean', data=df_axes_melted, palette=colors)
plt.title('Vibração Média por Eixo (Decomposição Espacial)', fontsize=14)
plt.ylabel('Aceleração (g)')
plt.xlabel('Eixos Ortogonais')
plt.legend(title='Estado')
plt.tight_layout()
plt.savefig(f'{output_dir}/10_rms_por_eixo.png', dpi=300)

# ---------------------------------------------------------
# 11. DENSIDADE CORRIGIDA (Separando as "Ilhas")
# ---------------------------------------------------------
# Usando Banda 1 (Eixo Z) vs RMS Global para garantir separação física
plt.figure(figsize=(10, 8))
g = sns.JointGrid(data=df_active, x='z_energy_band_1', y='rms_global', hue='status_clean', palette=colors)
g.plot_joint(sns.kdeplot, fill=True, alpha=0.5, thresh=0, levels=10)
g.plot_marginals(sns.histplot, alpha=0.5)
g.fig.suptitle('Análise de Clusters: Identificação Automática de Falhas', fontsize=14)
g.ax_joint.set_xlabel('Energia na Banda 1 (Assinatura de Desbalanceamento)')
g.ax_joint.set_ylabel('Vibração Total (g)')
g.fig.subplots_adjust(top=0.9)
plt.savefig(f'{output_dir}/11_densidade_corrigida_ilhas.png', dpi=300)

print(f"Sucesso! Gráficos salvos em {output_dir}")
