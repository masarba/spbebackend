const xlsx = require("xlsx");
const fs = require("fs");

// Membaca file Excel
const workbook = xlsx.readFile("SPBE - Alat Bantu Audit.xlsx");

// Ambil data dari sheet pertama
const sheetName = workbook.SheetNames[0];
const sheetData = xlsx.utils.sheet_to_json(workbook.Sheets[sheetName]);

// Simpan data sebagai JSON
fs.writeFileSync("questions.json", JSON.stringify(sheetData, null, 2));
console.log("Data berhasil dikonversi ke JSON!");
