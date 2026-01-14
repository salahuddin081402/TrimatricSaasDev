START TRANSACTION;

-- =========================================================
-- KHULNA DIVISION — 10 districts
-- =========================================================

-- Khulna
INSERT INTO upazilas (district_id, name, status) SELECT id,'Batiaghata',1 FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dacope',1     FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dighalia',1   FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dumuria',1    FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Koyra',1      FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Paikgacha',1  FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Phultala',1   FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rupsha',1     FROM districts WHERE name='Khulna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Terokhada',1  FROM districts WHERE name='Khulna';

-- Bagerhat
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bagerhat Sadar',1 FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chitalmari',1     FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fakirhat',1       FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kachua',1         FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mollahat',1       FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mongla',1         FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Morrelganj',1     FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rampal',1         FROM districts WHERE name='Bagerhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sarankhola',1     FROM districts WHERE name='Bagerhat';

-- Satkhira
INSERT INTO upazilas (district_id, name, status) SELECT id,'Satkhira Sadar',1 FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Assasuni',1       FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Debhata',1        FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalaroa',1        FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaliganj',1       FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shyamnagar',1     FROM districts WHERE name='Satkhira';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tala',1           FROM districts WHERE name='Satkhira';

-- Jashore
INSERT INTO upazilas (district_id, name, status) SELECT id,'Abhaynagar',1   FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bagherpara',1   FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chaugachha',1   FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jhikargacha',1  FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Keshabpur',1    FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manirampur',1   FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sharsha',1      FROM districts WHERE name='Jashore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jashore Sadar',1 FROM districts WHERE name='Jashore';

-- Narail
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalia',1           FROM districts WHERE name='Narail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lohagara',1        FROM districts WHERE name='Narail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Narail Sadar',1    FROM districts WHERE name='Narail';

-- Magura
INSERT INTO upazilas (district_id, name, status) SELECT id,'Magura Sadar',1 FROM districts WHERE name='Magura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mohammadpur',1  FROM districts WHERE name='Magura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shalikha',1     FROM districts WHERE name='Magura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sreepur',1      FROM districts WHERE name='Magura';

-- Jhenaidah
INSERT INTO upazilas (district_id, name, status) SELECT id,'Harinakunda',1      FROM districts WHERE name='Jhenaidah';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jhenaidah Sadar',1  FROM districts WHERE name='Jhenaidah';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaliganj',1         FROM districts WHERE name='Jhenaidah';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kotchandpur',1      FROM districts WHERE name='Jhenaidah';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Maheshpur',1        FROM districts WHERE name='Jhenaidah';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shailkupa',1        FROM districts WHERE name='Jhenaidah';

-- Chuadanga
INSERT INTO upazilas (district_id, name, status) SELECT id,'Alamdanga',1      FROM districts WHERE name='Chuadanga';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chuadanga Sadar',1 FROM districts WHERE name='Chuadanga';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Damurhuda',1      FROM districts WHERE name='Chuadanga';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jibannagar',1     FROM districts WHERE name='Chuadanga';

-- Kushtia
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bheramara',1       FROM districts WHERE name='Kushtia';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Daulatpur',1       FROM districts WHERE name='Kushtia';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Khoksa',1          FROM districts WHERE name='Kushtia';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kumarkhali',1      FROM districts WHERE name='Kushtia';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kushtia Sadar',1   FROM districts WHERE name='Kushtia';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mirpur',1          FROM districts WHERE name='Kushtia';

-- Meherpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gangni',1          FROM districts WHERE name='Meherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Meherpur Sadar',1  FROM districts WHERE name='Meherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mujibnagar',1      FROM districts WHERE name='Meherpur';


-- =========================================================
-- RAJSHAHI DIVISION — 8 districts
-- =========================================================

-- Rajshahi
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bagha',1      FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bagmara',1    FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Charghat',1   FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Durgapur',1   FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Godagari',1   FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mohanpur',1   FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Paba',1       FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Puthia',1     FROM districts WHERE name='Rajshahi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tanore',1     FROM districts WHERE name='Rajshahi';

-- Natore
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bagatipara',1   FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baraigram',1    FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gurudaspur',1   FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lalpur',1       FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Naldanga',1     FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Natore Sadar',1 FROM districts WHERE name='Natore';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Singra',1       FROM districts WHERE name='Natore';

-- Naogaon
INSERT INTO upazilas (district_id, name, status) SELECT id,'Atrai',1            FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Badalgachhi',1      FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhamoirhat',1       FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manda',1            FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mohadevpur',1       FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Naogaon Sadar',1    FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Niamatpur',1        FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Patnitala',1        FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Porsha',1           FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raninagar',1        FROM districts WHERE name='Naogaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sapahar',1          FROM districts WHERE name='Naogaon';

-- Chapai Nawabganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chapai Nawabganj Sadar',1 FROM districts WHERE name='Chapai Nawabganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shibganj',1                FROM districts WHERE name='Chapai Nawabganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gomastapur',1              FROM districts WHERE name='Chapai Nawabganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nachole',1                 FROM districts WHERE name='Chapai Nawabganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bholahat',1                FROM districts WHERE name='Chapai Nawabganj';

-- Joypurhat
INSERT INTO upazilas (district_id, name, status) SELECT id,'Akkelpur',1         FROM districts WHERE name='Joypurhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Joypurhat Sadar',1  FROM districts WHERE name='Joypurhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalai',1            FROM districts WHERE name='Joypurhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Khetlal',1          FROM districts WHERE name='Joypurhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Panchbibi',1        FROM districts WHERE name='Joypurhat';

-- Bogura
INSERT INTO upazilas (district_id, name, status) SELECT id,'Adamdighi',1       FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bogura Sadar',1    FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhunat',1          FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhupchanchia',1    FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gabtali',1         FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kahaloo',1         FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nandigram',1       FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sariakandi',1      FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shajahanpur',1     FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shibganj',1        FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sherpur',1         FROM districts WHERE name='Bogura';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sonatala',1        FROM districts WHERE name='Bogura';

-- Sirajganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Belkuchi',1      FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chauhali',1      FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kamarkhanda',1   FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kazipur',1       FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raiganj',1       FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shahjadpur',1    FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sirajganj Sadar',1 FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tarash',1        FROM districts WHERE name='Sirajganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ullahpara',1     FROM districts WHERE name='Sirajganj';

-- Pabna
INSERT INTO upazilas (district_id, name, status) SELECT id,'Atgharia',1     FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bera',1         FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhangura',1     FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chatmohar',1    FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Faridpur',1     FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ishwardi',1     FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pabna Sadar',1  FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Santhia',1      FROM districts WHERE name='Pabna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sujanagar',1    FROM districts WHERE name='Pabna';


-- =========================================================
-- RANGPUR DIVISION — 8 districts
-- =========================================================

-- Rangpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Badarganj',1      FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gangachara',1     FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaunia',1         FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mithapukur',1     FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pirgacha',1       FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pirganj',1        FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rangpur Sadar',1  FROM districts WHERE name='Rangpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Taraganj',1       FROM districts WHERE name='Rangpur';

-- Dinajpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Biral',1          FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Birampur',1       FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Birganj',1        FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bochaganj',1      FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chirirbandar',1   FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dinajpur Sadar',1 FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ghoraghat',1      FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hakimpur',1       FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaharole',1       FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Khansama',1       FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nawabganj',1      FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Parbatipur',1     FROM districts WHERE name='Dinajpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Phulbari',1       FROM districts WHERE name='Dinajpur';

-- Thakurgaon
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baliadangi',1        FROM districts WHERE name='Thakurgaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Haripur',1           FROM districts WHERE name='Thakurgaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pirganj',1           FROM districts WHERE name='Thakurgaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ranisankail',1       FROM districts WHERE name='Thakurgaon';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Thakurgaon Sadar',1  FROM districts WHERE name='Thakurgaon';

-- Panchagarh
INSERT INTO upazilas (district_id, name, status) SELECT id,'Atwari',1           FROM districts WHERE name='Panchagarh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Boda',1             FROM districts WHERE name='Panchagarh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Debiganj',1         FROM districts WHERE name='Panchagarh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Panchagarh Sadar',1 FROM districts WHERE name='Panchagarh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tetulia',1          FROM districts WHERE name='Panchagarh';

-- Nilphamari
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dimla',1                 FROM districts WHERE name='Nilphamari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Domar',1                 FROM districts WHERE name='Nilphamari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jaldhaka',1              FROM districts WHERE name='Nilphamari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kishoreganj',1           FROM districts WHERE name='Nilphamari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nilphamari Sadar',1      FROM districts WHERE name='Nilphamari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Saidpur',1               FROM districts WHERE name='Nilphamari';

-- Lalmonirhat
INSERT INTO upazilas (district_id, name, status) SELECT id,'Aditmari',1         FROM districts WHERE name='Lalmonirhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hatibandha',1       FROM districts WHERE name='Lalmonirhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaliganj',1         FROM districts WHERE name='Lalmonirhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lalmonirhat Sadar',1 FROM districts WHERE name='Lalmonirhat';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Patgram',1          FROM districts WHERE name='Lalmonirhat';

-- Kurigram
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhurungamari',1     FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Char Rajibpur',1    FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chilmari',1         FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kurigram Sadar',1   FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nageshwari',1       FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Phulbari',1         FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajarhat',1         FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raomari',1          FROM districts WHERE name='Kurigram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ulipur',1           FROM districts WHERE name='Kurigram';

-- Gaibandha
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fulchhari',1       FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gaibandha Sadar',1 FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gobindaganj',1     FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Palashbari',1      FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sadullapur',1      FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Saghata',1         FROM districts WHERE name='Gaibandha';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sundarganj',1      FROM districts WHERE name='Gaibandha';


-- =========================================================
-- SYLHET DIVISION — 4 districts
-- =========================================================

-- Sylhet
INSERT INTO upazilas (district_id, name, status) SELECT id,'Balaganj',1        FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Beanibazar',1      FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bishwanath',1      FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Companiganj',1     FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dakshin Surma',1   FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fenchuganj',1      FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Golapganj',1       FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gowainghat',1      FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jaintiapur',1      FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kanaighat',1       FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Osmaninagar',1     FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sylhet Sadar',1    FROM districts WHERE name='Sylhet';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Zakiganj',1        FROM districts WHERE name='Sylhet';

-- Moulvibazar
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barlekha',1          FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Juri',1               FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kamalganj',1          FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kulaura',1            FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Moulvibazar Sadar',1  FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajnagar',1           FROM districts WHERE name='Moulvibazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sreemangal',1         FROM districts WHERE name='Moulvibazar';

-- Habiganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ajmiriganj',1      FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bahubal',1         FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baniachang',1      FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chunarughat',1     FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Habiganj Sadar',1  FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lakhai',1          FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madhabpur',1       FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nabiganj',1        FROM districts WHERE name='Habiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shayestaganj',1    FROM districts WHERE name='Habiganj';

-- Sunamganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bishwamvarpur',1     FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chhatak',1           FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dakshin Sunamganj',1 FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Derai',1             FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dharampasha',1       FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dowarabazar',1       FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jagannathpur',1      FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jamalganj',1         FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sullah',1            FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sunamganj Sadar',1   FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tahirpur',1          FROM districts WHERE name='Sunamganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shantiganj',1        FROM districts WHERE name='Sunamganj';


-- =========================================================
-- BARISHAL DIVISION — 6 districts
-- =========================================================

-- Barishal
INSERT INTO upazilas (district_id, name, status) SELECT id,'Agailjhara',1     FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Babuganj',1       FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bakerganj',1      FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Banaripara',1     FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barishal Sadar',1 FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gaurnadi',1       FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hizla',1          FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mehendiganj',1    FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Muladi',1         FROM districts WHERE name='Barishal';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Wazirpur',1       FROM districts WHERE name='Barishal';

-- Barguna
INSERT INTO upazilas (district_id, name, status) SELECT id,'Amtali',1           FROM districts WHERE name='Barguna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bamna',1            FROM districts WHERE name='Barguna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barguna Sadar',1    FROM districts WHERE name='Barguna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Betagi',1           FROM districts WHERE name='Barguna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Patharghata',1      FROM districts WHERE name='Barguna';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Taltali',1          FROM districts WHERE name='Barguna';

-- Bhola
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhola Sadar',1    FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Borhanuddin',1    FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Char Fasson',1    FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Daulatkhan',1     FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lalmohan',1       FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manpura',1        FROM districts WHERE name='Bhola';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tazumuddin',1     FROM districts WHERE name='Bhola';

-- Jhalokathi
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jhalokathi Sadar',1 FROM districts WHERE name='Jhalokathi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kathalia',1         FROM districts WHERE name='Jhalokathi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nalchity',1         FROM districts WHERE name='Jhalokathi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajapur',1          FROM districts WHERE name='Jhalokathi';

-- Patuakhali
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bauphal',1            FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dashmina',1           FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dumki',1              FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Galachipa',1          FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalapara',1           FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mirzaganj',1          FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Patuakhali Sadar',1   FROM districts WHERE name='Patuakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rangabali',1          FROM districts WHERE name='Patuakhali';

-- Pirojpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhandaria',1             FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Indurkani',1             FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kawkhali',1              FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mathbaria',1             FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nesarabad (Swarupkathi)',1 FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nazirpur',1              FROM districts WHERE name='Pirojpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pirojpur Sadar',1        FROM districts WHERE name='Pirojpur';


-- =========================================================
-- MYMENSINGH DIVISION — 4 districts
-- =========================================================

-- Mymensingh
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhaluka',1          FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhobaura',1         FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fulbaria',1         FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gaffargaon',1       FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gauripur',1         FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Haluaghat',1        FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ishwarganj',1       FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mymensingh Sadar',1 FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Muktagacha',1       FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nandail',1          FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Phulpur',1          FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tarakanda',1        FROM districts WHERE name='Mymensingh';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Trishal',1          FROM districts WHERE name='Mymensingh';

-- Jamalpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baksiganj',1        FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dewanganj',1        FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Islampur',1         FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jamalpur Sadar',1   FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madarganj',1        FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Melandaha',1        FROM districts WHERE name='Jamalpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sarishabari',1      FROM districts WHERE name='Jamalpur';

-- Netrokona
INSERT INTO upazilas (district_id, name, status) SELECT id,'Atpara',1            FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barhatta',1          FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Durgapur',1          FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalmakanda',1        FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kendua',1            FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Khaliajuri',1        FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madan',1             FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mohanganj',1         FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Netrokona Sadar',1   FROM districts WHERE name='Netrokona';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Purbadhala',1        FROM districts WHERE name='Netrokona';

-- Sherpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Jhenaigati',1       FROM districts WHERE name='Sherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nakla',1            FROM districts WHERE name='Sherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nalitabari',1       FROM districts WHERE name='Sherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sherpur Sadar',1    FROM districts WHERE name='Sherpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sreebardi',1        FROM districts WHERE name='Sherpur';

COMMIT;
