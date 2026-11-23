<?php
// Sumber data statis untuk datalist: negara (ISO 3166-1, nama Indonesia) dan kota.
// Tidak ada API/online; user tetap boleh mengetik nilai di luar daftar.

const NEGARA = [
    'Afganistan', 'Afrika Selatan', 'Albania', 'Aljazair', 'Amerika Serikat', 'Andorra', 'Angola',
    'Antigua dan Barbuda', 'Arab Saudi', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
    'Bahama', 'Bahrain', 'Bangladesh', 'Barbados', 'Belanda', 'Belarus', 'Belgia', 'Belize', 'Benin',
    'Bhutan', 'Bolivia', 'Bosnia dan Herzegovina', 'Botswana', 'Brasil', 'Brunei Darussalam', 'Bulgaria',
    'Burkina Faso', 'Burundi', 'Ceko', 'Chad', 'Chili', 'China', 'Denmark', 'Djibouti', 'Dominika',
    'Ekuador', 'El Salvador', 'Eritrea', 'Estonia', 'Eswatini', 'Etiopia', 'Fiji', 'Filipina', 'Finlandia',
    'Gabon', 'Gambia', 'Georgia', 'Ghana', 'Grenada', 'Guatemala', 'Guinea', 'Guinea Bissau', 'Guyana',
    'Haiti', 'Honduras', 'Hungaria', 'India', 'Indonesia', 'Inggris', 'Irak', 'Iran', 'Irlandia', 'Islandia',
    'Israel', 'Italia', 'Jamaika', 'Jepang', 'Jerman', 'Kamboja', 'Kamerun', 'Kanada', 'Kazakhstan', 'Kenya',
    'Kepulauan Marshall', 'Kepulauan Solomon', 'Kirgizstan', 'Kiribati', 'Kolombia', 'Komoro', 'Kongo',
    'Korea Selatan', 'Korea Utara', 'Kosta Rika', 'Kroasia', 'Kuba', 'Kuwait', 'Laos', 'Latvia', 'Lebanon',
    'Lesoto', 'Liberia', 'Libya', 'Liechtenstein', 'Lituania', 'Luksemburg', 'Madagaskar', 'Makau', 'Maladewa',
    'Malawi', 'Malaysia', 'Mali', 'Malta', 'Maroko', 'Mauritania', 'Mauritius', 'Meksiko', 'Mesir', 'Mikronesia',
    'Moldova', 'Monako', 'Mongolia', 'Montenegro', 'Mozambik', 'Myanmar', 'Namibia', 'Nauru', 'Nepal',
    'Selandia Baru', 'Nikaragua', 'Niger', 'Nigeria', 'Makedonia Utara', 'Norwegia', 'Oman', 'Pakistan',
    'Palau', 'Palestina', 'Panama', 'Papua Nugini', 'Paraguay', 'Prancis', 'Peru', 'Polandia', 'Portugal',
    'Qatar', 'Republik Afrika Tengah', 'Republik Demokratik Kongo', 'Republik Dominika', 'Rumania', 'Rusia',
    'Rwanda', 'Saint Kitts dan Nevis', 'Saint Lucia', 'Saint Vincent dan Grenadines', 'Samoa', 'San Marino',
    'Sao Tome dan Principe', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapura', 'Siprus', 'Slovakia',
    'Slovenia', 'Somalia', 'Spanyol', 'Sri Lanka', 'Sudan', 'Sudan Selatan', 'Suriah', 'Suriname', 'Swedia',
    'Swiss', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor Leste', 'Togo', 'Tonga', 'Trinidad dan Tobago',
    'Tunisia', 'Turki', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraina', 'Uni Emirat Arab', 'Uruguay', 'Uzbekistan',
    'Vanuatu', 'Vatikan', 'Venezuela', 'Vietnam', 'Yaman', 'Yordania', 'Yunani', 'Zambia', 'Zimbabwe',
];

const KOTA = [
    'Jakarta', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara',
    'Bandung', 'Bekasi', 'Bogor', 'Depok', 'Tangerang', 'Tangerang Selatan', 'Serang', 'Cilegon',
    'Yogyakarta', 'Sleman', 'Bantul', 'Magelang', 'Surakarta', 'Semarang', 'Salatiga', 'Pekalongan',
    'Tegal', 'Surabaya', 'Malang', 'Kediri', 'Madiun', 'Mojokerto', 'Pasuruan', 'Probolinggo', 'Blitar',
    'Denpasar', 'Mataram', 'Kupang', 'Pontianak', 'Singkawang', 'Banjarmasin', 'Banjarbaru', 'Palangka Raya',
    'Samarinda', 'Balikpapan', 'Bontang', 'Tarakan', 'Tanjungpinang', 'Batam', 'Pekanbaru', 'Dumai',
    'Padang', 'Bukittinggi', 'Payakumbuh', 'Solok', 'Jambi', 'Sungai Penuh', 'Palembang', 'Prabumulih',
    'Lubuklinggau', 'Pagar Alam', 'Bengkulu', 'Bandar Lampung', 'Metro', 'Pangkalpinang', 'Tanjung Pandan',
    'Makassar', 'Parepare', 'Palopo', 'Kendari', 'Bau-Bau', 'Manado', 'Bitung', 'Tomohon', 'Kotamobagu',
    'Gorontalo', 'Palu', 'Poso', 'Ambon', 'Tual', 'Ternate', 'Tidore Kepulauan', 'Sorong', 'Jayapura',
    'Manokwari', 'Merauke', 'Timika', 'Dili', 'Kuala Lumpur', 'Singapura', 'Bangkok', 'Manila', 'Hanoi',
    'Tokyo', 'Seoul', 'Beijing', 'Shanghai', 'New Delhi', 'Dubai', 'Riyadh', 'Kairo', 'London', 'Paris',
    'Amsterdam', 'Berlin', 'Madrid', 'Roma', 'Moskow', 'New York', 'Los Angeles', 'San Francisco', 'Toronto',
    'Sydney', 'Melbourne', 'Auckland', 'Sao Paulo', 'Mexico City', 'Buenos Aires',
];
