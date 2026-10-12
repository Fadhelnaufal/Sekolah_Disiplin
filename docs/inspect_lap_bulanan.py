import pandas as pd

excel_file = "Disiplin Pro tata tertib siswa V-1.xlsx"
df = pd.read_excel(excel_file, sheet_name='LAP_BULANAN', nrows=20)
print("--- SHEET: LAP_BULANAN ---")
print(df.to_string(index=False, na_rep=''))
