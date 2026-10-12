import pandas as pd

excel_file = "Disiplin Pro tata tertib siswa V-1.xlsx"

print("--- SHEET: REKAP_WALIKELAS ---")
df_rekap = pd.read_excel(excel_file, sheet_name='REKAP_WALIKELAS', nrows=15)
print(df_rekap.to_string(index=False, na_rep=''))

print("--- SHEET: ANALISIS ---")
df_analisis = pd.read_excel(excel_file, sheet_name='ANALISIS', nrows=15)
print(df_analisis.to_string(index=False, na_rep=''))
