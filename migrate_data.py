import pymongo
from pymongo import MongoClient
from unidecode import unidecode
import random
import re

# Connection String
MONGO_URI = "mongodb+srv://admin:Wkv81fE5t2QM@nongtriphat.yxggdz6.mongodb.net/NongTriPhat?retryWrites=true&w=majority&tlsInsecure=true"
DB_NAME = "NongTriPhat"

# Data Lists
dvts = ['Bao 20 Kg', 'Bao 25kg', 'Bao 50kg', 'Chai', 'Cái', 'Cặp', 'Gói', 'Hủ', 'Viên', 'Xô']

loais = [
    'Phân bón gốc', 
    'Phân bón lá', 
    'Thuốc trừ sâu', 
    'Thuốc trừ bệnh', 
    'Thuốc trừ cỏ', 
    'Thuốc kích thích sinh trưởng',
    'Hạt giống',
    'Dụng cụ nông nghiệp',
    'Giá thể - Đất sạch',
    'Khác'
]

ncc_names = ['A Nhiều', 'BÒ VÀNG', 'Bảy Phận', 'HAI', 'Kimagri', 'King Azone', 'Lộc Trời', 'Nam Á', 'P&D', 'Phân Phạm Hoàng', 'Phân Siếu Việt', 'Phân Thuận Mùa', 'Phân Tân Thành', 'Phân Việt Nga', 'Quốc Bảo', 'Sang QCL', 'Thuốc Tân Thành', 'Thế Mẫn', 'Thọ', 'Trung ương 1', 'Tâm Nông Phú', 'Việt Á', 'a Nghĩa', 'Đại Nghĩa']

goods = [{"ten": "Humic Usa Viên", "dvt": "Gói", "gia_von": 30000}, {"ten": "Tesla 20ml", "dvt": "Gói", "gia_von": 21000}, {"ten": "Chim Sâu Gói 30ml", "dvt": "Gói", "gia_von": 17000}, {"ten": "Emagol 20g", "dvt": "Gói", "gia_von": 9000}, {"ten": "Vitashil - 450ml", "dvt": "Chai", "gia_von": 95000}, {"ten": "Muỗi Hành Đục Thân Btr - 450ml", "dvt": "Chai", "gia_von": 70000}, {"ten": "Siêu Sâu Ống 50g", "dvt": "Gói", "gia_von": 14000}, {"ten": "Thần Dược G4+7 - 100ml", "dvt": "Chai", "gia_von": 75000}, {"ten": "Green Kep - 240ml", "dvt": "Chai", "gia_von": 170000}, {"ten": "Top Top - 240ml", "dvt": "Chai", "gia_von": 135000}, {"ten": "Bim 100gr", "dvt": "Gói", "gia_von": 37000}, {"ten": "Bim 30gr", "dvt": "Gói", "gia_von": 12500}, {"ten": "Đồng Kasu - 150gr", "dvt": "Gói", "gia_von": 55000}, {"ten": "Top Bean 240ml", "dvt": "Chai", "gia_von": 75000}, {"ten": "Khuẩn Bạc Lá - 450ml", "dvt": "Chai", "gia_von": 62000}, {"ten": "Khuẩn Lạnh - 450ml", "dvt": "Chai", "gia_von": 45000}, {"ten": "Anwil Kẽm Ấn Độ - 1 Lít", "dvt": "Chai", "gia_von": 68000}, {"ten": "M8 Singapore - 100gr", "dvt": "Gói", "gia_von": 21500}, {"ten": "Help Rice - 240ml", "dvt": "Chai", "gia_von": 135000}, {"ten": "Đặc Trị Đạo Ôn 100gr", "dvt": "Gói", "gia_von": 31000}, {"ten": "Trừ Nấm Sát Khuẩn 100gr", "dvt": "Gói", "gia_von": 22000}, {"ten": "Tilt USA - 240ml", "dvt": "Chai", "gia_von": 75000}, {"ten": "Yapoko 240ml", "dvt": "Chai", "gia_von": 55000}, {"ten": "Canxibo hữu cơ - 500ml", "dvt": "Chai", "gia_von": 60000}, {"ten": "Bio Ra Rễ Gói - 35gr", "dvt": "Gói", "gia_von": 6000}, {"ten": "Dưỡng Đồng Hữu Cơ - 500ml", "dvt": "Chai", "gia_von": 55000}, {"ten": "Bio Vô Gạo - 35gr", "dvt": "Gói", "gia_von": 7000}, {"ten": "Humic Vảy", "dvt": "Gói", "gia_von": 42000}, {"ten": "Trợ Lực - 100ml", "dvt": "Chai", "gia_von": 20000}, {"ten": "Gold Rice - 500ml", "dvt": "Chai", "gia_von": 60000}, {"ten": "Glufoxone 150SL", "dvt": "Chai", "gia_von": 50000}, {"ten": "Glufoxam 200SL", "dvt": "Chai", "gia_von": 90000}, {"ten": "Maco Xanh Ấn Độ 1 kg", "dvt": "Gói", "gia_von": 120000}, {"ten": "Diệt Sâu Và Nhện - 450ml", "dvt": "Chai", "gia_von": 50000}, {"ten": "Đẻ nhánh 20-15-5", "dvt": "Bao 50kg", "gia_von": 745000}, {"ten": "Đạm Cà Mau", "dvt": "Bao 50kg", "gia_von": 645000}, {"ten": "Ure Phú Mỹ", "dvt": "Bao 50kg", "gia_von": 660000}, {"ten": "Đạm Đen Cà Mau", "dvt": "Bao 50kg", "gia_von": 655000}, {"ten": "DAP Humic vinacam", "dvt": "Bao 50kg", "gia_von": 1105000}, {"ten": "Siêu gà T5 (Viên)", "dvt": "Bao 50kg", "gia_von": 170000}, {"ten": "Siêu gà T5 (Bột)", "dvt": "Bao 50kg", "gia_von": 170000}, {"ten": "Lân Đen Cá (Viên)", "dvt": "Bao 50kg", "gia_von": 150000}, {"ten": "Gà Hàn Quốc (Viên)", "dvt": "Bao 20 Kg", "gia_von": 160000}, {"ten": "Lân Trắng", "dvt": "Bao 20 Kg", "gia_von": 145000}, {"ten": "Bio Humic TM", "dvt": "Gói", "gia_von": 50000}, {"ten": "Bio Trico TM", "dvt": "Gói", "gia_von": 42000}, {"ten": "Thau tặng khách", "dvt": "Cái", "gia_von": 0}, {"ten": "Áo Thun Việt Á", "dvt": "Cái", "gia_von": 0}, {"ten": "Amistar top - 240ml", "dvt": "Chai", "gia_von": 268000}, {"ten": "Tilt Super 300 EC - 250ml", "dvt": "Chai", "gia_von": 190000}, {"ten": "Antracol 70 WP - 100 gr", "dvt": "Gói", "gia_von": 31000}, {"ten": "Ridomin gold 68WG -100gr", "dvt": "Gói", "gia_von": 53000}, {"ten": "Anvil 5SC - 1 lít", "dvt": "Chai", "gia_von": 261000}, {"ten": "Coc 85 - 100 gr", "dvt": "Gói", "gia_von": 32000}, {"ten": "Khai hoang Q10", "dvt": "Chai", "gia_von": 34000}, {"ten": "Atonik 1.8DD - 10 ml", "dvt": "Gói", "gia_von": 7000}, {"ten": "Comcat 150 WP A-Z", "dvt": "Gói", "gia_von": 11000}, {"ten": "AZ Rice - 200gr", "dvt": "Gói", "gia_von": 60000}, {"ten": "Vô Gạo KA - 250ml", "dvt": "Chai", "gia_von": 70000}, {"ten": "Vô Gạo KA - 500ml", "dvt": "Chai", "gia_von": 90000}, {"ten": "Fugi 125 gr - Thuốc trừ nấm", "dvt": "Gói", "gia_von": 120000}, {"ten": "Fuba 500 ml - Sạch nấm khuẩn", "dvt": "Chai", "gia_von": 90000}, {"ten": "Baci 1 lít - Xử lý đất", "dvt": "Chai", "gia_von": 200000}, {"ten": "3T 250 gr - Tuyến trùng", "dvt": "Gói", "gia_von": 120000}, {"ten": "Tora 500ml - Dưỡng Trái", "dvt": "Chai", "gia_von": 90000}, {"ten": "Canxi bo 500ml - KA", "dvt": "Chai", "gia_von": 90000}, {"ten": "Nasa 500ml - Cứng cây", "dvt": "Chai", "gia_von": 90000}, {"ten": "Dova 25gr - Đạo Ôn", "dvt": "Gói", "gia_von": 28000}, {"ten": "Tricoderma - pbvs KA", "dvt": "Gói", "gia_von": 75000}, {"ten": "Lacasoto 18gr", "dvt": "Gói", "gia_von": 16500}, {"ten": "Keep 200ml", "dvt": "Chai", "gia_von": 119000}, {"ten": "Kinalux 480ml", "dvt": "Chai", "gia_von": 106000}, {"ten": "Scooter 250ml", "dvt": "Chai", "gia_von": 76000}, {"ten": "Paramax 400SC 250ml", "dvt": "Chai", "gia_von": 170000}, {"ten": "Total 100 gr", "dvt": "Gói", "gia_von": 55000}, {"ten": "Evitin 1 Lít", "dvt": "Chai", "gia_von": 83000}, {"ten": "Pylaconl 1 kg", "dvt": "Gói", "gia_von": 210000}, {"ten": "Huracan a200", "dvt": "Chai", "gia_von": 40000}, {"ten": "Racumin 20gr", "dvt": "Gói", "gia_von": 14000}, {"ten": "New Beem 100gr", "dvt": "Gói", "gia_von": 45000}, {"ten": "Dekamon - 10ml", "dvt": "Gói", "gia_von": 6200}, {"ten": "RidoZed 1kg", "dvt": "Gói", "gia_von": 219000}, {"ten": "Mancozed Xanh 1kg HAI", "dvt": "Gói", "gia_von": 135000}, {"ten": "Felling 1kg", "dvt": "Gói", "gia_von": 225000}, {"ten": "Hopsan 450ml", "dvt": "Chai", "gia_von": 130000}, {"ten": "Vival 1kg", "dvt": "Gói", "gia_von": 238000}, {"ten": "Flower 500ml", "dvt": "Chai", "gia_von": 90000}, {"ten": "Navi 250ml", "dvt": "Chai", "gia_von": 280000}, {"ten": "Chess Rầy 800 - 20gr", "dvt": "Gói", "gia_von": 22000}, {"ten": "Super roots 500ml", "dvt": "Chai", "gia_von": 55000}, {"ten": "NewCol 629WP -100gr", "dvt": "Gói", "gia_von": 26000}, {"ten": "Magie Kẽm 1kg", "dvt": "Gói", "gia_von": 70000}, {"ten": "Lân 86 - 1kg", "dvt": "Gói", "gia_von": 87000}, {"ten": "Canxi silic 100gr", "dvt": "Gói", "gia_von": 7000}, {"ten": "ERASE 200SL -900 ml", "dvt": "Chai", "gia_von": 66000}, {"ten": "Cặp Đôi 600ml", "dvt": "Cặp", "gia_von": 95000}, {"ten": "Chess Rầy 800 - 125gr", "dvt": "Chai", "gia_von": 135000}, {"ten": "Đón và nuôi đồng 18-8-20", "dvt": "Bao 50kg", "gia_von": 725000}, {"ten": "Pexena 106SC - 20ml", "dvt": "Gói", "gia_von": 83000}, {"ten": "Pexena 106SC - chai 20ml", "dvt": "Chai", "gia_von": 76000}, {"ten": "DAP 21-53-0", "dvt": "Xô", "gia_von": 1000000}, {"ten": "NPK 19-19-19", "dvt": "Xô", "gia_von": 850000}, {"ten": "NPK 22-22-22", "dvt": "Xô", "gia_von": 950000}, {"ten": "Siêu Tạo Mầm 0-40-6", "dvt": "Xô", "gia_von": 1000000}, {"ten": "Vua nhú đọt - 50ml", "dvt": "Gói", "gia_von": 6000}, {"ten": "Lớn Trái Mít - 500ml", "dvt": "Chai", "gia_von": 66000}, {"ten": "Khắc tinh bọ trĩ\xa0 35WG", "dvt": "Gói", "gia_von": 8800}, {"ten": "Knock Out - 240ml", "dvt": "Chai", "gia_von": 60000}, {"ten": "Siêu lùn - 500ml", "dvt": "Chai", "gia_von": 55000}, {"ten": "Rầy Thái - 100gr", "dvt": "Gói", "gia_von": 28000}, {"ten": "Vali 50WP - 40gr", "dvt": "Gói", "gia_von": 3200}, {"ten": "Incipio 200SC - 8ml", "dvt": "Gói", "gia_von": 36500}, {"ten": "Pexena cốm\xa0 20WG - 10gr", "dvt": "Gói", "gia_von": 66000}, {"ten": "Aliette 800WG -100gr", "dvt": "Gói", "gia_von": 52000}, {"ten": "Xantocin - 40 WP - 100gr", "dvt": "Gói", "gia_von": 68000}, {"ten": "NPK 16-16-8 Việt Nhật", "dvt": "Bao 50kg", "gia_von": 725000}, {"ten": "DAP KOREA (đen)", "dvt": "Bao 50kg", "gia_von": 1345000}, {"ten": "NPK 25-25-5 Nano", "dvt": "Bao 50kg", "gia_von": 925000}, {"ten": "Kali Miễng Cà Mau", "dvt": "Bao 50kg", "gia_von": 575000}, {"ten": "Kali Miễng Canada", "dvt": "Bao 50kg", "gia_von": 575000}, {"ten": "PH 30-10-10 Xanh dương", "dvt": "Bao 25kg", "gia_von": 390000}, {"ten": "PH 20-20-15 Xanh lá", "dvt": "Bao 25kg", "gia_von": 430000}, {"ten": "PH 17-17-17 Trắng", "dvt": "Bao 25kg", "gia_von": 440000}, {"ten": "PH 15-5-25 Tím", "dvt": "Bao 25kg", "gia_von": 400000}, {"ten": "Lân Indo gold", "dvt": "Bao 50kg", "gia_von": 150000}, {"ten": "Hữu cơ ORGANFUL 1Lít", "dvt": "Chai", "gia_von": 160000}, {"ten": "Hữu cơ gà Bỉ -25kg", "dvt": "", "gia_von": 0}, {"ten": "16-16-8 Phi - Hồng - 50kg", "dvt": "Bao 50kg", "gia_von": 685000}, {"ten": "VN 16-16-16 - Hồng - 25kg", "dvt": "Bao 25kg", "gia_von": 410000}, {"ten": "VN 20-20-15 TE - 25kg", "dvt": "Bao 25kg", "gia_von": 440000}, {"ten": "VN 17-17-17 - 25kg", "dvt": "Bao 25kg", "gia_von": 425000}, {"ten": "20-20-15 + TE Đầu Trâu", "dvt": "Bao 50kg", "gia_von": 960000}, {"ten": "Reflect Xtra -200ml", "dvt": "Chai", "gia_von": 272000}, {"ten": "Starwiner 20 WP -100gr", "dvt": "Gói", "gia_von": 69000}, {"ten": "Starwiner 20 WP -25gr", "dvt": "Gói", "gia_von": 18600}, {"ten": "Mataxyl 500 WP - 100gr", "dvt": "Gói", "gia_von": 79000}, {"ten": "Nativo - 60gr", "dvt": "Gói", "gia_von": 158000}, {"ten": "Baci 500ml - Xử lý đất", "dvt": "Chai", "gia_von": 100000}, {"ten": "DAP ĐEN NGA VINA", "dvt": "Bao 50kg", "gia_von": 1125000}, {"ten": "NPK 20-10-10 Cà Mau", "dvt": "Bao 50kg", "gia_von": 635000}, {"ten": "P-D Lân 86 1kg", "dvt": "Gói", "gia_von": 105000}, {"ten": "P-D Ra hoa mít - 500ml", "dvt": "Chai", "gia_von": 48300}, {"ten": "P-D MKP - 250gam", "dvt": "Gói", "gia_von": 21750}, {"ten": "P-D GA3 Sữa - 500ml", "dvt": "Chai", "gia_von": 78750}, {"ten": "P-D 10-60-10 - 250gam", "dvt": "Gói", "gia_von": 23000}, {"ten": "DEKAMON - 100 ml", "dvt": "Chai", "gia_von": 47500}, {"ten": "GA3 Viên", "dvt": "Viên", "gia_von": 16000}, {"ten": "ROOT -500ml", "dvt": "Chai", "gia_von": 50000}, {"ten": "Siêu Lớn Trái Mít - 1 lít", "dvt": "Chai", "gia_von": 90000}, {"ten": "Lớn Trái mít - 25kg", "dvt": "Xô", "gia_von": 850000}, {"ten": "Vali 8SL -1 lít", "dvt": "Chai", "gia_von": 70000}, {"ten": "Gemistar - 100ml", "dvt": "Chai", "gia_von": 78000}, {"ten": "Super cat 10g", "dvt": "Gói", "gia_von": 11500}, {"ten": "Ortus 5sc - 100ml", "dvt": "Chai", "gia_von": 49245}, {"ten": "BV 30-10-10", "dvt": "Bao 25kg", "gia_von": 320000}, {"ten": "BV 17-17-17", "dvt": "Bao 25kg", "gia_von": 320000}, {"ten": "BV 20-20-15", "dvt": "Bao 25kg", "gia_von": 320000}, {"ten": "Super Lân Kẽm 500ml", "dvt": "Chai", "gia_von": 70000}, {"ten": "Comcast 500ml", "dvt": "Chai", "gia_von": 55000}, {"ten": "Vịt Bầu Đỏ", "dvt": "Gói", "gia_von": 44000}, {"ten": "Bavimin Gold 18.7WG", "dvt": "Gói", "gia_von": 82000}, {"ten": "Anhead 12GB", "dvt": "Gói", "gia_von": 47000}, {"ten": "AGR 6-30-30 - 500gr", "dvt": "Gói", "gia_von": 37500}, {"ten": "AGR 30-10-10- 500gr", "dvt": "Gói", "gia_von": 34500}, {"ten": "AGR - Gel Lớn Mít", "dvt": "Chai", "gia_von": 69000}, {"ten": "AGR- Super max", "dvt": "Xô", "gia_von": 740000}, {"ten": "QCL- Nuôi Mít Nhỏ - 500ml", "dvt": "Chai", "gia_von": 54000}, {"ten": "P-D Amino - 500ml", "dvt": "Chai", "gia_von": 53550}, {"ten": "P-D Ra hoa đậu trái -30gr", "dvt": "Gói", "gia_von": 7000}, {"ten": "Chặn Đọt - 500ml", "dvt": "Chai", "gia_von": 70000}, {"ten": "Magie Kẽm 160gr", "dvt": "Gói", "gia_von": 16000}, {"ten": "Kasu 3% - 480ml", "dvt": "Chai", "gia_von": 59000}, {"ten": "Lân 2 chiều 1 lít", "dvt": "Chai", "gia_von": 125000}, {"ten": "Regen 240ml", "dvt": "Chai", "gia_von": 62000}, {"ten": "HNM Meco 60EC 1lít", "dvt": "Chai", "gia_von": 183000}, {"ten": "TNM Weeder 300EC 500ml", "dvt": "Chai", "gia_von": 103000}, {"ten": "TNM Weeder 300EC 1lít", "dvt": "Chai", "gia_von": 198000}, {"ten": "Akka 3.6 450ml", "dvt": "Chai", "gia_von": 54000}, {"ten": "TM DAP 18-46 Philipin", "dvt": "Bao 50kg", "gia_von": 1370000}, {"ten": "Nông Gia Hưng 10g", "dvt": "Gói", "gia_von": 10200}, {"ten": "Bloom USA - 500ml", "dvt": "Chai", "gia_von": 58000}, {"ten": "SV Hữu Cơ TN - 25Kg", "dvt": "Bao 25kg", "gia_von": 205000}, {"ten": "SV Canxi bo - 25Kg", "dvt": "Bao 25kg", "gia_von": 290000}, {"ten": "SV DAP Xanh 50 Kg", "dvt": "Bao 50kg", "gia_von": 1260000}, {"ten": "Trebon 400ml", "dvt": "Chai", "gia_von": 95172}, {"ten": "C.ru 1kg", "dvt": "Hủ", "gia_von": 260000}]

def generate_code(name, existing_codes):
    """Generates a short code from the name using first letters of words"""
    cleaned = unidecode(name).upper()
    # Remove non-alphanumeric chars (keep underscores, spaces) then split
    words = re.sub(r'[^A-Z0-9\s]', '', cleaned).split()
    
    # Get first char of each word
    prefix = "".join([w[0] for w in words])
    
    # Ensure prefix is at least 2 chars
    if len(prefix) < 2:
        prefix = cleaned[:3].replace(" ", "")
        
    # Append number
    counter = 1
    while True:
        code = f"{prefix}{counter:03}"
        if code not in existing_codes:
            existing_codes.add(code)
            return code
        counter += 1

def generate_supplier_info(name):
    # Fake info generation
    districts = ["Châu Thành", "Mỹ Xuyên", "Trần Đề", "Kế Sách", "Long Phú", "Cù Lao Dung", "Thạnh Trị", "Ngã Năm"]
    road_types = ["Ấp", "Đường", "Khu vực"]
    
    district = random.choice(districts)
    road = random.choice(road_types)
    number = random.randint(1, 999)
    
    phone_prefixes = ["090", "091", "093", "098", "097", "033", "034", "079"]
    phone = f"{random.choice(phone_prefixes)}{random.randint(1000000, 9999999)}"
    
    email_name = unidecode(name).lower().replace(" ", "")
    email = f"contact.{email_name}@gmail.com"
    
    address = f"{road} {number}, {district}, Sóc Trăng"
    
    return {
        "ten": name,
        "dia_chi": address,
        "dien_thoai": phone,
        "email": email,
        "ghi_chu": ""
    }

def run_migration():
    try:
        print("Connecting to MongoDB...")
        client = MongoClient(MONGO_URI)
        db = client[DB_NAME]
        
        # Collections
        don_vi_tinh_col = db["don_vi_tinh"]
        nha_cung_cap_col = db["nha_cung_cap"]
        hang_hoa_col = db["hang_hoa"]
        
        # 0. Wipe Data
        print("Wiping existing data...")
        don_vi_tinh_col.delete_many({})
        nha_cung_cap_col.delete_many({})
        hang_hoa_col.delete_many({})
        print("Data wiped.")
        
        print("Migrating Units (Don Vi Tinh)...")
        # 1. Insert Units
        for name in dvts:
            don_vi_tinh_col.insert_one({"ten": name})
            
        # Build map name -> _id
        dvt_map = {}
        for doc in don_vi_tinh_col.find():
            dvt_map[doc["ten"]] = doc["_id"]
                
        print(f"Loaded {len(dvt_map)} units.")
        
        print("Migrating Suppliers (Nha Cung Cap)...")
        # 2. Insert Suppliers with enriched info
        for name in ncc_names:
            supplier_data = generate_supplier_info(name)
            nha_cung_cap_col.insert_one(supplier_data)
            
        print("Migrating Goods (Hang Hoa)...")
        # 3. Insert Goods with new logic
        existing_codes = set()
        count = 0
        
        for item in goods:
            # Generate Code
            ma = generate_code(item["ten"], existing_codes)
            
            dvt_id = dvt_map.get(item["dvt"])
            
            # Pricing Logic
            gia_von = item["gia_von"]
            if gia_von > 0:
                # Retail (Le) = Cost + 40k~50k
                margin_le = random.randint(30, 50) * 1000
                gia_le = gia_von + margin_le
                
                # Wholesale (Si)/Credit = Cost + 20k~30k (User implied "ban thieu" logic)
                # User req: "gia ban mat va ban thieu (si va le) do doi so voi gia von khoang 20k-50k"
                # Let's define:
                # Gia ban mat (Cash) ~ Gia le
                # Gia ban thieu (Credit) ~ High margin
                # Gia si ~ Low margin
                
                # Let's simplify to what fits the request best:
                # 20k - 50k range.
                
                gia_le = gia_von + random.randint(20, 30) * 1000 # Lower margin for cash
                gia_si = gia_von + random.randint(40, 50) * 1000 # Higher margin for credit
            else:
                 gia_ban_mat = 0
                 gia_ban_thieu = 0
                 gia_si = 0
                 gia_le = 0

            
            hang_hoa_col.insert_one({
                "ma": ma,
                "ten": item["ten"],
                "id_donvitinh": dvt_id,
                "gia_von": gia_von,
                "gia_ban_mat": gia_ban_mat,
                "gia_ban_thieu": gia_ban_thieu,
                "gia_si": gia_si,
                "gia_le": gia_le,
                "so_luong_ton": 0, # Reset stock
                "ghi_chu": "" # Clear notes
            })
            count += 1
            
        print(f"Successfully processed {count} goods.")
        print("Migration Completed!")

    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    run_migration()
