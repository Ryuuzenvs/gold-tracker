
import mysql.connector
import requests
from bs4 import BeautifulSoup
from datetime import date
import os
from dotenv import load_dotenv
import re

load_dotenv()

def get_gold_price():
    try:
        # URL Baru yang kamu temukan
        url = "https://pluang.com/explore/metals-plus/gold"
        headers = {
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        }
        response = requests.get(url, headers=headers, timeout=15)
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Seringkali di Pluang harga dibungkus dalam tag yang memiliki 
        # class spesifik atau teks "Rp" di depannya.
        # Kita cari semua text yang mengandung "Rp" dan angka jutaan
        price_tags = soup.find_all(string=re.compile(r'Rp\s?2\.\d{3}\.\d{3}'))
        
        if not price_tags:
            # Plan B: Cari selector umum untuk harga di Pluang
            # Biasanya menggunakan class yang deskriptif
            potential_price = soup.find('div', string=re.compile(r'Rp'))
            if potential_price:
                price_text = potential_price.text
            else:
                return None
        else:
            price_text = price_tags[0]

        # Regex untuk ambil angka saja: Rp 2.910.352 -> 2910352
        clean_price = int(re.sub(r'\D', '', price_text))
        return clean_price
        
    except Exception as e:
        print(f"Scrape Error: {e}")
        return None

# ... fungsi save_to_db
def save_to_db(price):
    try:
        db = mysql.connector.connect(
            host=os.getenv("DB_HOST"),
            user=os.getenv("DB_USER"),
            password=os.getenv("DB_PASS"),
            database=os.getenv("DB_NAME")
        )
        cursor = db.cursor()
        
        # Hitung Spread 4%
        price_sell = int(price * 0.96)
        today = date.today()
        
        # Cek ATH (All Time High)
        cursor.execute("SELECT MAX(price_buy) FROM gold_history")
        max_price = cursor.fetchone()[0] or 0
        is_ath = 1 if price > max_price else 0
        
        # Logic Gate: Insert if not exists today
        sql = "INSERT IGNORE INTO gold_history (price_buy, price_sell, is_ath, created_at) VALUES (%s, %s, %s, %s)"
        val = (price, price_sell, is_ath, today)
        
        cursor.execute(sql, val)
        db.commit()
        
        if cursor.rowcount > 0:
            print(f"Data Berhasil Disimpan: Rp{price:,}")
        else:
            print("Data sudah ada untuk hari ini.")
            
    except mysql.connector.Error as err:
        print(f"DB Error: {err}")
    finally:
        if 'db' in locals(): db.close()


if __name__ == "__main__":
    print("Memulai Scraping Harga Emas...")
    current_price = get_gold_price()
    if current_price:
        print(f"Harga ditemukan: Rp{current_price:,}")
        save_to_db(current_price) # Buka comment ini kalau sudah yakin angkanya benar
    else:
        print("Gagal mengambil harga. Cek koneksi atau selector web.")
