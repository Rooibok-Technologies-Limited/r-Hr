--
-- PostgreSQL database: erp_hrsale
-- Converted from MySQL schema
--

--
-- Database: erp_hrsale
--

-- --------------------------------------------------------

--
-- Table structure for table ci_advance_salary
--

CREATE TABLE ci_advance_salary (
    advance_salary_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    salary_type varchar(100) DEFAULT NULL,
    month_year varchar(255) NOT NULL,
    advance_amount NUMERIC(12,2) NOT NULL,
    one_time_deduct varchar(50) NOT NULL,
    monthly_installment NUMERIC(12,2) NOT NULL,
    total_paid NUMERIC(12,2) NOT NULL,
    reason text NOT NULL,
    status INTEGER DEFAULT NULL,
    is_deducted_from_salary INTEGER DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_advance_salary
--

TRUNCATE TABLE ci_advance_salary;
-- --------------------------------------------------------

--
-- Table structure for table ci_announcements
--

CREATE TABLE ci_announcements (
    announcement_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    department_id varchar(255) NOT NULL,
    title varchar(200) NOT NULL,
    start_date varchar(200) NOT NULL,
    end_date varchar(200) NOT NULL,
    published_by INTEGER NOT NULL,
    summary TEXT NOT NULL,
    description TEXT NOT NULL,
    is_active SMALLINT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_announcements
--

TRUNCATE TABLE ci_announcements;
-- --------------------------------------------------------

--
-- Table structure for table ci_assets
--

CREATE TABLE ci_assets (
    assets_id SERIAL PRIMARY KEY,
    assets_category_id INTEGER NOT NULL,
    brand_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    company_asset_code varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    purchase_date varchar(255) NOT NULL,
    invoice_number varchar(255) NOT NULL,
    manufacturer varchar(255) NOT NULL,
    serial_number varchar(255) NOT NULL,
    warranty_end_date varchar(255) NOT NULL,
    asset_note text NOT NULL,
    asset_image varchar(255) NOT NULL,
    is_working INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_assets
--

TRUNCATE TABLE ci_assets;
-- --------------------------------------------------------

--
-- Table structure for table ci_awards
--

CREATE TABLE ci_awards (
    award_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    award_type_id INTEGER NOT NULL,
    associated_goals text DEFAULT NULL,
    gift_item varchar(200) NOT NULL,
    cash_price NUMERIC(12,2) NOT NULL,
    award_photo varchar(255) NOT NULL,
    award_month_year varchar(200) NOT NULL,
    award_information TEXT NOT NULL,
    description TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_awards
--

TRUNCATE TABLE ci_awards;
-- --------------------------------------------------------

--
-- Table structure for table ci_company_membership
--

CREATE TABLE ci_company_membership (
    company_membership_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    membership_id INTEGER NOT NULL,
    subscription_type varchar(25) NOT NULL,
    update_at varchar(100) DEFAULT NULL,
    created_at varchar(100) NOT NULL
);


--
-- Truncate table before insert ci_company_membership
--

TRUNCATE TABLE ci_company_membership;
--
-- Dumping data for table ci_company_membership
--

INSERT INTO ci_company_membership (company_membership_id, company_id, membership_id, subscription_type, update_at, created_at) VALUES
(1, 2, 6, '2', '2021-05-17 04:07:01', '2021-05-17 04:07:01');

-- --------------------------------------------------------

--
-- Table structure for table ci_complaints
--

CREATE TABLE ci_complaints (
    complaint_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    complaint_from INTEGER NOT NULL,
    title varchar(255) NOT NULL,
    complaint_date varchar(255) NOT NULL,
    complaint_against TEXT NOT NULL,
    description TEXT NOT NULL,
    status SMALLINT NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_complaints
--

TRUNCATE TABLE ci_complaints;
-- --------------------------------------------------------

--
-- Table structure for table ci_contract_options
--

CREATE TABLE ci_contract_options (
    contract_option_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    salay_type varchar(200) DEFAULT NULL,
    contract_tax_option INTEGER NOT NULL,
    is_fixed INTEGER NOT NULL,
    option_title varchar(200) DEFAULT NULL,
    contract_amount NUMERIC(12,2) DEFAULT 0.00
);


--
-- Truncate table before insert ci_contract_options
--

TRUNCATE TABLE ci_contract_options;
-- --------------------------------------------------------

--
-- Table structure for table ci_countries
--

CREATE TABLE ci_countries (
    country_id SERIAL PRIMARY KEY,
    country_code varchar(255) NOT NULL,
    country_name varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_countries
--

TRUNCATE TABLE ci_countries;
--
-- Dumping data for table ci_countries
--

INSERT INTO ci_countries (country_id, country_code, country_name) VALUES
(1, '+93', 'Afghanistan'),
(2, '+355', 'Albania'),
(3, 'DZ', 'Algeria'),
(4, 'DS', 'American Samoa'),
(5, 'AD', 'Andorra'),
(6, 'AO', 'Angola'),
(7, 'AI', 'Anguilla'),
(8, 'AQ', 'Antarctica'),
(9, 'AG', 'Antigua and Barbuda'),
(10, 'AR', 'Argentina'),
(11, 'AM', 'Armenia'),
(12, 'AW', 'Aruba'),
(13, 'AU', 'Australia'),
(14, 'AT', 'Austria'),
(15, 'AZ', 'Azerbaijan'),
(16, 'BS', 'Bahamas'),
(17, 'BH', 'Bahrain'),
(18, 'BD', 'Bangladesh'),
(19, 'BB', 'Barbados'),
(20, 'BY', 'Belarus'),
(21, 'BE', 'Belgium'),
(22, 'BZ', 'Belize'),
(23, 'BJ', 'Benin'),
(24, 'BM', 'Bermuda'),
(25, 'BT', 'Bhutan'),
(26, 'BO', 'Bolivia'),
(27, 'BA', 'Bosnia and Herzegovina'),
(28, 'BW', 'Botswana'),
(29, 'BV', 'Bouvet Island'),
(30, 'BR', 'Brazil'),
(31, 'IO', 'British Indian Ocean Territory'),
(32, 'BN', 'Brunei Darussalam'),
(33, 'BG', 'Bulgaria'),
(34, 'BF', 'Burkina Faso'),
(35, 'BI', 'Burundi'),
(36, 'KH', 'Cambodia'),
(37, 'CM', 'Cameroon'),
(38, 'CA', 'Canada'),
(39, 'CV', 'Cape Verde'),
(40, 'KY', 'Cayman Islands'),
(41, 'CF', 'Central African Republic'),
(42, 'TD', 'Chad'),
(43, 'CL', 'Chile'),
(44, 'CN', 'China'),
(45, 'CX', 'Christmas Island'),
(46, 'CC', 'Cocos (Keeling) Islands'),
(47, 'CO', 'Colombia'),
(48, 'KM', 'Comoros'),
(49, 'CG', 'Congo'),
(50, 'CK', 'Cook Islands'),
(51, 'CR', 'Costa Rica'),
(52, 'HR', 'Croatia (Hrvatska)'),
(53, 'CU', 'Cuba'),
(54, 'CY', 'Cyprus'),
(55, 'CZ', 'Czech Republic'),
(56, 'DK', 'Denmark'),
(57, 'DJ', 'Djibouti'),
(58, 'DM', 'Dominica'),
(59, 'DO', 'Dominican Republic'),
(60, 'TP', 'East Timor'),
(61, 'EC', 'Ecuador'),
(62, 'EG', 'Egypt'),
(63, 'SV', 'El Salvador'),
(64, 'GQ', 'Equatorial Guinea'),
(65, 'ER', 'Eritrea'),
(66, 'EE', 'Estonia'),
(67, 'ET', 'Ethiopia'),
(68, 'FK', 'Falkland Islands (Malvinas)'),
(69, 'FO', 'Faroe Islands'),
(70, 'FJ', 'Fiji'),
(71, 'FI', 'Finland'),
(72, 'FR', 'France'),
(73, 'FX', 'France, Metropolitan'),
(74, 'GF', 'French Guiana'),
(75, 'PF', 'French Polynesia'),
(76, 'TF', 'French Southern Territories'),
(77, 'GA', 'Gabon'),
(78, 'GM', 'Gambia'),
(79, 'GE', 'Georgia'),
(80, 'DE', 'Germany'),
(81, 'GH', 'Ghana'),
(82, 'GI', 'Gibraltar'),
(83, 'GK', 'Guernsey'),
(84, 'GR', 'Greece'),
(85, 'GL', 'Greenland'),
(86, 'GD', 'Grenada'),
(87, 'GP', 'Guadeloupe'),
(88, 'GU', 'Guam'),
(89, 'GT', 'Guatemala'),
(90, 'GN', 'Guinea'),
(91, 'GW', 'Guinea-Bissau'),
(92, 'GY', 'Guyana'),
(93, 'HT', 'Haiti'),
(94, 'HM', 'Heard and Mc Donald Islands'),
(95, 'HN', 'Honduras'),
(96, 'HK', 'Hong Kong'),
(97, 'HU', 'Hungary'),
(98, 'IS', 'Iceland'),
(99, 'IN', 'India'),
(100, 'IM', 'Isle of Man'),
(101, 'ID', 'Indonesia'),
(102, 'IR', 'Iran (Islamic Republic of)'),
(103, 'IQ', 'Iraq'),
(104, 'IE', 'Ireland'),
(105, 'IL', 'Israel'),
(106, 'IT', 'Italy'),
(107, 'CI', 'Ivory Coast'),
(108, 'JE', 'Jersey'),
(109, 'JM', 'Jamaica'),
(110, 'JP', 'Japan'),
(111, 'JO', 'Jordan'),
(112, 'KZ', 'Kazakhstan'),
(113, 'KE', 'Kenya'),
(114, 'KI', 'Kiribati'),
(115, 'KP', 'Korea, Democratic People''s Republic of'),
(116, 'KR', 'Korea, Republic of'),
(117, 'XK', 'Kosovo'),
(118, 'KW', 'Kuwait'),
(119, 'KG', 'Kyrgyzstan'),
(120, 'LA', 'Lao People''s Democratic Republic'),
(121, 'LV', 'Latvia'),
(122, 'LB', 'Lebanon'),
(123, 'LS', 'Lesotho'),
(124, 'LR', 'Liberia'),
(125, 'LY', 'Libyan Arab Jamahiriya'),
(126, 'LI', 'Liechtenstein'),
(127, 'LT', 'Lithuania'),
(128, 'LU', 'Luxembourg'),
(129, 'MO', 'Macau'),
(130, 'MK', 'Macedonia'),
(131, 'MG', 'Madagascar'),
(132, 'MW', 'Malawi'),
(133, 'MY', 'Malaysia'),
(134, 'MV', 'Maldives'),
(135, 'ML', 'Mali'),
(136, 'MT', 'Malta'),
(137, 'MH', 'Marshall Islands'),
(138, 'MQ', 'Martinique'),
(139, 'MR', 'Mauritania'),
(140, 'MU', 'Mauritius'),
(141, 'TY', 'Mayotte'),
(142, 'MX', 'Mexico'),
(143, 'FM', 'Micronesia, Federated States of'),
(144, 'MD', 'Moldova, Republic of'),
(145, 'MC', 'Monaco'),
(146, 'MN', 'Mongolia'),
(147, 'ME', 'Montenegro'),
(148, 'MS', 'Montserrat'),
(149, 'MA', 'Morocco'),
(150, 'MZ', 'Mozambique'),
(151, 'MM', 'Myanmar'),
(152, 'NA', 'Namibia'),
(153, 'NR', 'Nauru'),
(154, 'NP', 'Nepal'),
(155, 'NL', 'Netherlands'),
(156, 'AN', 'Netherlands Antilles'),
(157, 'NC', 'New Caledonia'),
(158, 'NZ', 'New Zealand'),
(159, 'NI', 'Nicaragua'),
(160, 'NE', 'Niger'),
(161, 'NG', 'Nigeria'),
(162, 'NU', 'Niue'),
(163, 'NF', 'Norfolk Island'),
(164, 'MP', 'Northern Mariana Islands'),
(165, 'NO', 'Norway'),
(166, 'OM', 'Oman'),
(167, 'PK', 'Pakistan'),
(168, 'PW', 'Palau'),
(169, 'PS', 'Palestine'),
(170, 'PA', 'Panama'),
(171, 'PG', 'Papua New Guinea'),
(172, 'PY', 'Paraguay'),
(173, 'PE', 'Peru'),
(174, 'PH', 'Philippines'),
(175, 'PN', 'Pitcairn'),
(176, 'PL', 'Poland'),
(177, 'PT', 'Portugal'),
(178, 'PR', 'Puerto Rico'),
(179, 'QA', 'Qatar'),
(180, 'RE', 'Reunion'),
(181, 'RO', 'Romania'),
(182, 'RU', 'Russian Federation'),
(183, 'RW', 'Rwanda'),
(184, 'KN', 'Saint Kitts and Nevis'),
(185, 'LC', 'Saint Lucia'),
(186, 'VC', 'Saint Vincent and the Grenadines'),
(187, 'WS', 'Samoa'),
(188, 'SM', 'San Marino'),
(189, 'ST', 'Sao Tome and Principe'),
(190, 'SA', 'Saudi Arabia'),
(191, 'SN', 'Senegal'),
(192, 'RS', 'Serbia'),
(193, 'SC', 'Seychelles'),
(194, 'SL', 'Sierra Leone'),
(195, 'SG', 'Singapore'),
(196, 'SK', 'Slovakia'),
(197, 'SI', 'Slovenia'),
(198, 'SB', 'Solomon Islands'),
(199, 'SO', 'Somalia'),
(200, 'ZA', 'South Africa'),
(201, 'GS', 'South Georgia South Sandwich Islands'),
(202, 'ES', 'Spain'),
(203, 'LK', 'Sri Lanka'),
(204, 'SH', 'St. Helena'),
(205, 'PM', 'St. Pierre and Miquelon'),
(206, 'SD', 'Sudan'),
(207, 'SR', 'Suriname'),
(208, 'SJ', 'Svalbard and Jan Mayen Islands'),
(209, 'SZ', 'Swaziland'),
(210, 'SE', 'Sweden'),
(211, 'CH', 'Switzerland'),
(212, 'SY', 'Syrian Arab Republic'),
(213, 'TW', 'Taiwan'),
(214, 'TJ', 'Tajikistan'),
(215, 'TZ', 'Tanzania, United Republic of'),
(216, 'TH', 'Thailand'),
(217, 'TG', 'Togo'),
(218, 'TK', 'Tokelau'),
(219, 'TO', 'Tonga'),
(220, 'TT', 'Trinidad and Tobago'),
(221, 'TN', 'Tunisia'),
(222, 'TR', 'Turkey'),
(223, 'TM', 'Turkmenistan'),
(224, 'TC', 'Turks and Caicos Islands'),
(225, 'TV', 'Tuvalu'),
(226, 'UG', 'Uganda'),
(227, 'UA', 'Ukraine'),
(228, 'AE', 'United Arab Emirates'),
(229, 'GB', 'United Kingdom'),
(230, 'US', 'United States'),
(231, 'UM', 'United States minor outlying islands'),
(232, 'UY', 'Uruguay'),
(233, 'UZ', 'Uzbekistan'),
(234, 'VU', 'Vanuatu'),
(235, 'VA', 'Vatican City State'),
(236, 'VE', 'Venezuela'),
(237, 'VN', 'Vietnam'),
(238, 'VG', 'Virgin Islands (British)'),
(239, 'VI', 'Virgin Islands (U.S.)'),
(240, 'WF', 'Wallis and Futuna Islands'),
(241, 'EH', 'Western Sahara'),
(242, 'YE', 'Yemen'),
(243, 'ZR', 'Zaire'),
(244, 'ZM', 'Zambia'),
(245, 'ZW', 'Zimbabwe');

-- --------------------------------------------------------

--
-- Table structure for table ci_currencies
--

CREATE TABLE ci_currencies (
    currency_id SERIAL PRIMARY KEY,
    country_name varchar(150) NOT NULL,
    currency_name varchar(20) NOT NULL,
    currency_code varchar(20) NOT NULL
);


--
-- Truncate table before insert ci_currencies
--

TRUNCATE TABLE ci_currencies;
--
-- Dumping data for table ci_currencies
--

INSERT INTO ci_currencies (currency_id, country_name, currency_name, currency_code) VALUES
(1, 'Afghanistan', 'Afghan afghani', 'AFN'),
(2, 'Albania', 'Albanian lek', 'ALL'),
(3, 'Algeria', 'Algerian dinar', 'DZD'),
(6, 'Angola', 'Angolan kwanza', 'AOA'),
(10, 'Argentina', 'Argentine peso', 'ARS'),
(11, 'Armenia', 'Armenian dram', 'AMD'),
(12, 'Aruba', 'Aruban florin', 'AWG'),
(13, 'Australia', 'Australian dollar', 'AUD'),
(15, 'Azerbaijan', 'Azerbaijani manat', 'AZN'),
(16, 'Bahamas', 'Bahamian dollar', 'BSD'),
(17, 'Bahrain', 'Bahraini dinar', 'BHD'),
(18, 'Bangladesh', 'Bangladeshi taka', 'BDT'),
(19, 'Barbados', 'Barbadian dollar', 'BBD'),
(20, 'Belarus', 'Belarusian ruble', 'BYR'),
(22, 'Belize', 'Belize dollar', 'BZD'),
(24, 'Bermuda', 'Bermudian dollar', 'BMD'),
(25, 'Bhutan', 'Bhutanese ngultrum', 'BTN'),
(26, 'Bolivia', 'Bolivian boliviano', 'BOB'),
(27, 'Bosnia and Herzegovina', 'Bosnia and Herzegovi', 'BAM'),
(30, 'Brazil', 'Brazilian real', 'BRL'),
(33, 'Bulgaria', 'Bulgarian lev', 'BGN'),
(35, 'Burundi', 'Burundian franc', 'BIF'),
(36, 'Cambodia', 'Cambodian riel', 'KHR'),
(38, 'Canada', 'Canadian dollar', 'CAD'),
(39, 'Cape Verde', 'Cape Verdean escudo', 'CVE'),
(40, 'Cayman Islands', 'Cayman Islands dolla', 'KYD'),
(43, 'Chile', 'Chilean peso', 'CLP'),
(44, 'China', 'Chinese yuan', 'CNY'),
(47, 'Colombia', 'Colombian peso', 'COP'),
(48, 'Comoros', 'Comorian franc', 'KMF'),
(49, 'Congo', 'Congolese franc', 'CDF'),
(52, 'Costa Rica', 'Costa Rican', 'CRC'),
(54, 'Croatia (Hrvatska)', 'Croatian kuna', 'HRK'),
(55, 'Cuba', 'Cuban convertible pe', 'CUC'),
(57, 'Czech Republic', 'Czech koruna', 'CZK'),
(58, 'Denmark', 'Danish krone', 'DKK'),
(59, 'Djibouti', 'Djiboutian franc', 'DJF'),
(60, 'Dominica', 'East Caribbean dolla', 'XCD'),
(61, 'Dominican Republic', 'Dominican peso', 'DOP'),
(64, 'Egypt', 'Egyptian pound', 'EGP'),
(67, 'Eritrea', 'Eritrean nakfa', 'ERN'),
(69, 'Ethiopia', 'Ethiopian birr', 'ETB'),
(71, 'Falkland Islands', 'Falkland Islands pou', 'FKP'),
(73, 'Fiji Islands', 'Fiji Dollars', 'FJD'),
(79, 'Gabon', 'Central African CFA ', 'XAF'),
(80, 'Gambia The', 'Gambian dalasi', 'GMD'),
(81, 'Georgia', 'Georgian lari', 'GEL'),
(83, 'Ghana', 'Ghana cedi', 'GHS'),
(84, 'Gibraltar', 'Gibraltar pound', 'GIP'),
(90, 'Guatemala', 'Guatemalan quetzal', 'GTQ'),
(92, 'Guinea', 'Guinean franc', 'GNF'),
(94, 'Guyana', 'Guyanese dollar', 'GYD'),
(95, 'Haiti', 'Haitian gourde', 'HTG'),
(97, 'Honduras', 'Honduran lempira', 'HNL'),
(98, 'Hong Kong S.A.R.', 'Hong Kong dollar', 'HKD'),
(99, 'Hungary', 'Hungarian forint', 'HUF'),
(100, 'Iceland', 'Icelandic króna\n', 'ISK'),
(101, 'India', 'Indian rupee', 'INR'),
(102, 'Indonesia', 'Indonesian rupiah', 'IDR'),
(103, 'Iran', 'Iranian rial', 'IRR'),
(104, 'Iraq', 'Iraqi dinar', 'IQD'),
(106, 'Israel', 'Israeli new shekel', 'ILS'),
(108, 'Jamaica', 'Jamaican dollar', 'JMD'),
(109, 'Japan', 'Japanese yen', 'JPY'),
(111, 'Jordan', 'Jordanian dinar', 'JOD'),
(112, 'Kazakhstan', 'Kazakhstani tenge', 'KZT'),
(113, 'Kenya', 'Kenyan shilling', 'KES'),
(115, 'Korea North', 'North Korean won', 'KPW'),
(116, 'Korea South', 'Korea (South) Won', 'KRW'),
(117, 'Kuwait', 'Kuwaiti dinar', 'KWD'),
(118, 'Kyrgyzstan', 'Kyrgyzstani som', 'KGS'),
(119, 'Laos', 'Lao kip', 'LAK'),
(121, 'Lebanon', 'Lebanese pound', 'LBP'),
(122, 'Lesotho', 'Lesotho loti', 'LSL'),
(123, 'Liberia', 'Liberian dollar', 'LRD'),
(124, 'Libya', 'Libyan dinar', 'LYD'),
(128, 'Macau S.A.R.', 'Macanese pataca', 'MOP'),
(129, 'Macedonia', 'Macedonian denar', 'MKD'),
(130, 'Madagascar', 'Malagasy ariary', 'MGA'),
(131, 'Malawi', 'Malawian kwacha', 'MWK'),
(132, 'Malaysia', 'Malaysian ringgit', 'MYR'),
(133, 'Maldives', 'Maldivian rufiyaa', 'MVR'),
(134, 'Mali', 'West African CFA fra', 'XOF'),
(136, 'Man (Isle of)', 'Manx pound', 'IMP'),
(139, 'Mauritania', 'Mauritanian ouguiya', 'MRO'),
(140, 'Mauritius', 'Mauritian rupee', 'MUR'),
(142, 'Mexico', 'Mexican peso', 'MXN'),
(144, 'Moldova', 'Moldovan leu', 'MDL'),
(146, 'Mongolia', 'Mongolian tögrög', 'MNT'),
(148, 'Morocco', 'Moroccan dirham', 'MAD'),
(149, 'Mozambique', 'Mozambican metical', 'MZN'),
(150, 'Myanmar', 'Burmese kyat', 'MMK'),
(151, 'Namibia', 'Namibian dollar', 'NAD'),
(153, 'Nepal', 'Nepalese rupee', 'NPR'),
(154, 'Netherlands Antilles', 'Dutch Guilder', 'ANG'),
(157, 'New Zealand', 'New Zealand dollar', 'NZD'),
(158, 'Nicaragua', 'Nicaraguan córdoba', 'NIO'),
(160, 'Nigeria', 'Nigerian naira', 'NGN'),
(164, 'Norway', 'Norwegian krone', 'NOK'),
(165, 'Oman', 'Omani rial', 'OMR'),
(166, 'Pakistan', 'Pakistani rupee', 'PKR'),
(169, 'Panama', 'Panamanian balboa', 'PAB'),
(170, 'Papua new Guinea', 'Papua New Guinean ki', 'PGK'),
(171, 'Paraguay', 'Paraguayan guaraní\n', 'PYG'),
(172, 'Peru', 'Peruvian nuevo sol', 'PEN'),
(173, 'Philippines', 'Philippine peso', 'PHP'),
(175, 'Poland', 'Polish złoty\n', 'PLN'),
(178, 'Qatar', 'Qatari riyal', 'QAR'),
(180, 'Romania', 'Romanian leu', 'RON'),
(181, 'Russia', 'Russian ruble', 'RUB'),
(182, 'Rwanda', 'Rwandan franc', 'RWF'),
(183, 'Saint Helena', 'Saint Helena pound', 'SHP'),
(188, 'Samoa', 'Samoan tālā\n', 'WST'),
(191, 'Saudi Arabia', 'Saudi riyal', 'SAR'),
(193, 'Serbia', 'Serbian dinar', 'RSD'),
(194, 'Seychelles', 'Seychellois rupee', 'SCR'),
(195, 'Sierra Leone', 'Sierra Leonean leone', 'SLL'),
(196, 'Singapore', 'Singapore dollar\n', 'SGD'),
(200, 'Solomon Islands', 'Solomon Islands doll', 'SBD'),
(201, 'Somalia', 'Somali shilling', 'SOS'),
(202, 'South Africa', 'South African rand', 'ZAR'),
(204, 'South Sudan', 'South Sudanese pound', 'SSP'),
(205, 'Spain', 'Euro', 'EUR'),
(206, 'Sri Lanka', 'Sri Lankan rupee', 'LKR'),
(207, 'Sudan', 'Sudanese pound', 'SDG'),
(208, 'Suriname', 'Surinamese dollar', 'SRD'),
(210, 'Swaziland', 'Swazi lilangeni', 'SZL'),
(211, 'Sweden', 'Swedish krona', 'SEK'),
(212, 'Switzerland', 'Swiss franc', 'CHF'),
(213, 'Syria', 'Syrian pound', 'SYP'),
(214, 'Taiwan', 'New Taiwan dollar', 'TWD'),
(215, 'Tajikistan', 'Tajikistani somoni', 'TJS'),
(216, 'Tanzania', 'Tanzanian shilling', 'TZS'),
(217, 'Thailand', 'Thai baht', 'THB'),
(220, 'Tonga', 'Tongan paʻanga\n', 'TOP'),
(221, 'Trinidad And Tobago', 'Trinidad and Tobago ', 'TTD'),
(222, 'Tunisia', 'Tunisian dinar', 'TND'),
(223, 'Turkey', 'Turkish lira', 'TRY'),
(224, 'Turkmenistan', 'Turkmenistan manat', 'TMT'),
(227, 'Uganda', 'Ugandan shilling', 'UGX'),
(228, 'Ukraine', 'Ukrainian hryvnia', 'UAH'),
(229, 'United Arab Emirates', 'United Arab Emirates', 'AED'),
(230, 'United Kingdom', 'British pound', 'GBP'),
(231, 'United States', 'United States Dollar', 'USD'),
(233, 'Uruguay', 'Uruguayan peso', 'UYU'),
(234, 'Uzbekistan', 'Uzbekistani som', 'UZS'),
(235, 'Vanuatu', 'Vanuatu vatu', 'VUV'),
(237, 'Venezuela', 'Venezuelan bolívar\n', 'VEF'),
(238, 'Vietnam', 'Vietnamese dong\n', 'VND'),
(241, 'Wallis And Futuna Islands', 'CFP franc', 'XPF'),
(243, 'Yemen', 'Yemeni rial', 'YER'),
(244, 'Yugoslavia', 'Yugoslav dinar', 'YUM'),
(245, 'Zambia', 'Zambian kwacha', 'ZMW'),
(246, 'Zimbabwe', 'Botswana pula', 'BWP');

-- --------------------------------------------------------

--
-- Table structure for table ci_database_backup
--

CREATE TABLE ci_database_backup (
    backup_id SERIAL PRIMARY KEY,
    backup_file varchar(255) NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_database_backup
--

TRUNCATE TABLE ci_database_backup;
-- --------------------------------------------------------

--
-- Table structure for table ci_departments
--

CREATE TABLE ci_departments (
    department_id SERIAL PRIMARY KEY,
    department_name varchar(200) NOT NULL,
    company_id INTEGER NOT NULL,
    department_head INTEGER DEFAULT 0,
    added_by INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_departments
--

TRUNCATE TABLE ci_departments;
-- --------------------------------------------------------

--
-- Table structure for table ci_designations
--

CREATE TABLE ci_designations (
    designation_id SERIAL PRIMARY KEY,
    department_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    designation_name varchar(200) NOT NULL,
    description text NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_designations
--

TRUNCATE TABLE ci_designations;
-- --------------------------------------------------------

--
-- Table structure for table ci_email_template
--

CREATE TABLE ci_email_template (
    template_id SERIAL PRIMARY KEY,
    template_code varchar(255) NOT NULL,
    template_type varchar(100) NOT NULL,
    name varchar(255) NOT NULL,
    subject varchar(255) NOT NULL,
    message TEXT NOT NULL,
    status SMALLINT NOT NULL
);


--
-- Truncate table before insert ci_email_template
--

TRUNCATE TABLE ci_email_template;
--
-- Dumping data for table ci_email_template
--

INSERT INTO ci_email_template (template_id, template_code, template_type, name, subject, message, status) VALUES
(1, 'code1', 'super_admin', 'Forgot Password', 'Forgot Password', '&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;There was recently a request for password for your {site_name} account.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;To reset password, visit the following link&lt;/span&gt;&amp;nbsp;&lt;a href=\"{site_url}erp/verified-password?v={user_id}\" title=\"Reset Password\" target=\"_blank\"&gt;&lt;strong&gt;&lt;span style=\"forced-color-adjust:none;color:#6699cc;\"&gt;Reset Password&lt;/span&gt;&lt;/strong&gt;&lt;/a&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;If this was a mistake, just ignore this email and nothing will happen.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(2, 'code1', 'super_admin', 'Password Changed Successfully', 'Password Changed Successfully', '&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Congratulations! Your password has been updated successfully.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Your Username is: {username}&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Your new password is: {password}&lt;/span&gt;&lt;br /&gt;&lt;/p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;{site_name}&lt;/span&gt;', 1),
(5, 'code1', 'super_admin', 'Add New Employee|Client|SuperAdmin User', 'Warm Welcome', '&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;box-sizing:border-box;\"&gt;&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Welcome to {site_name}. We have listed your sign-in details below, please make sure you keep them safe.&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Your Username is: {user_username}&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Your Password is: {user_password}&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;a href=\"{site_url}erp/login\" title=\"Login Here\" target=\"_blank\"&gt;Login Here&lt;/a&gt;&lt;/p&gt;&lt;p class=\"mt-4\" style=\"margin-bottom:0px;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;margin-top:1.5rem !important;\"&gt;----&lt;br style=\"box-sizing:border-box;\" /&gt;Thank You&lt;br style=\"box-sizing:border-box;\" /&gt;{site_name}&lt;/p&gt;', 1),
(8, 'code1', 'super_admin', 'Contact Us | From Frontend', 'Contact Us', '&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;box-sizing:border-box;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;This email was sent through your support contact form on {site_name}.&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Sender: {full_name}&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;Subject: {subject}&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;Email: {email}&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Message:&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;{message}&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You can reply directly to this email to respond to {full_name}&lt;/p&gt;&lt;p class=\"mt-4\" style=\"margin-bottom:0px;box-sizing:border-box;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;margin-top:1.5rem !important;\"&gt;----&lt;br style=\"box-sizing:border-box;\" /&gt;Thank You&lt;br style=\"box-sizing:border-box;\" /&gt;{&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;site_name&lt;/span&gt;}&lt;/p&gt;', 1),
(9, 'code1', 'super_admin', 'New Project Assigned', 'New Project Assigned', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;New project has been assigned to you.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Project Name: {project_name}&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Project Due Date: {project_due_date}&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(10, 'code1', 'super_admin', 'New Task Assigned', 'New Task Assigned', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;New task has been assigned to you.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Task Name: {task_name}&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Task Due Date: {task_due_date}&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(11, 'code1', 'super_admin', 'New Award', 'Award Received', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You have been awarded with &lt;span style=\"text-decoration:underline;\"&gt;{award_name}&lt;/span&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You can view this award by logging into the portal.&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(12, 'code1', 'super_admin', 'New Ticket Inquiry', 'New Inquiry [#{ticket_code}]', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You have received a new inquiry&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Inquiry code: &lt;span style=\"text-decoration:underline;\"&gt;&lt;strong&gt;#{ticket_code}&lt;/strong&gt;&lt;/span&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You can view this &lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;inquiry&amp;nbsp;&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;by logging into the portal.&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(13, 'code1', 'super_admin', 'New Leave Requested | For Company', 'New Leave Request', '&lt;p style=\"box-sizing:border-box;margin-bottom:1rem;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"box-sizing:border-box;margin-bottom:1rem;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;span style=\"text-decoration:underline;\"&gt;&lt;strong&gt;{employee_name}&lt;/strong&gt;&lt;/span&gt; wants a leave &lt;strong&gt;&lt;span style=\"text-decoration:underline;\"&gt;{leave_type}&lt;/span&gt;&lt;/strong&gt; from you. You can view the leave details by logging into the portal.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"box-sizing:border-box;margin-bottom:1rem;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(14, 'code1', 'super_admin', 'Leave Approved | For Employee', 'Your Leave Approved', '&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Congratulations! Your leave &lt;strong&gt;&lt;span style=\"text-decoration:underline;\"&gt;{leave_type}&lt;/span&gt;&lt;/strong&gt; request from {start_date} to {end_date} has been approved by your company management.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, Helvetica Neue, Arial, Noto Sans, sans-serif;\"&gt;&lt;span style=\"font-size:14px;\"&gt;Remarks:&lt;/span&gt;&lt;/span&gt;&lt;br style=\"font-style:normal;font-variant-ligatures:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;orphans:2;text-align:start;text-indent:0px;text-transform:none;white-space:normal;widows:2;word-spacing:0px;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"font-style:normal;font-variant-ligatures:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;orphans:2;text-align:start;text-indent:0px;text-transform:none;white-space:normal;widows:2;word-spacing:0px;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{remarks}&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;You can view the leave details by logging into the portal.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(15, 'code1', 'super_admin', 'Leave Rejected | For Employee', 'Your Leave Rejected', '&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Unfortunately! Your leave&amp;nbsp;&lt;strong&gt;&lt;span style=\"text-decoration-line:underline;\"&gt;{leave_type}&lt;/span&gt;&lt;/strong&gt;&amp;nbsp;request from {start_date} to {end_date} has been rejected by your company management.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, Helvetica Neue, Arial, Noto Sans, sans-serif;\"&gt;&lt;span style=\"font-size:14px;\"&gt;Reject Reason:&lt;/span&gt;&lt;/span&gt;&lt;br style=\"font-style:normal;font-variant-ligatures:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;orphans:2;text-align:start;text-indent:0px;text-transform:none;white-space:normal;widows:2;word-spacing:0px;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"font-style:normal;font-variant-ligatures:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;orphans:2;text-align:start;text-indent:0px;text-transform:none;white-space:normal;widows:2;word-spacing:0px;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{remarks}&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;You can view the leave details by logging into the portal.&lt;/span&gt;&lt;/p&gt;&lt;p style=\"margin-bottom:1rem;box-sizing:border-box;color:#4e5155;font-family:Roboto, -apple-system, BlinkMacSystemFont, ''Segoe UI'', Oxygen, Ubuntu, Cantarell, ''Fira Sans'', ''Droid Sans'', ''Helvetica Neue'', sans-serif;font-size:14.304px;background-color:#ffffff;\"&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(16, 'code1', 'super_admin', 'New Job Posted | For Employee', 'New Job Posted', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;We would like to announce a new vacancy for a &lt;strong&gt;&lt;span style=\"text-decoration:underline;\"&gt;{job_title}&lt;/span&gt;&lt;/strong&gt;.&lt;/span&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Suitable applicants can send submit their resumes before &lt;span style=\"text-decoration:underline;\"&gt;&lt;strong&gt;{closing_date}&lt;/strong&gt;&lt;/span&gt;&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You can view a complete job description by logging into the portal.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{site_name}&lt;/span&gt;&lt;/p&gt;', 1),
(17, 'code1', 'super_admin', 'Payslip Created | For Employee', 'Salary Slip for {month_year}', '&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;Hi There,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;New Payslip is created for {month_year}&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;You can view this payslip by logging into the portal.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;background-color:#ffffff;\"&gt;if you have any question, do not hesitate to contact your HR Department.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;----&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;Thank You,&lt;/span&gt;&lt;br style=\"color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;box-sizing:border-box;\" /&gt;&lt;span style=\"forced-color-adjust:none;color:#8094ae;font-family:Roboto, sans-serif, ''Helvetica Neue'', Arial, ''Noto Sans'', sans-serif;font-size:14px;\"&gt;{company_name}&lt;/span&gt;&lt;/p&gt;', 1);

-- --------------------------------------------------------

--
-- Table structure for table ci_employee_contacts
--

CREATE TABLE ci_employee_contacts (
    contact_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    relation varchar(255) DEFAULT NULL,
    is_primary INTEGER DEFAULT NULL,
    is_dependent INTEGER DEFAULT NULL,
    contact_name varchar(255) DEFAULT NULL,
    work_phone varchar(255) DEFAULT NULL,
    work_phone_extension varchar(255) DEFAULT NULL,
    mobile_phone varchar(255) DEFAULT NULL,
    home_phone varchar(255) DEFAULT NULL,
    work_email varchar(255) DEFAULT NULL,
    personal_email varchar(255) DEFAULT NULL,
    address_1 TEXT DEFAULT NULL,
    address_2 TEXT DEFAULT NULL,
    city varchar(255) DEFAULT NULL,
    state varchar(255) DEFAULT NULL,
    zipcode varchar(255) DEFAULT NULL,
    country varchar(255) DEFAULT NULL,
    created_at varchar(255) DEFAULT NULL
);


--
-- Truncate table before insert ci_employee_contacts
--

TRUNCATE TABLE ci_employee_contacts;
-- --------------------------------------------------------

--
-- Table structure for table ci_employee_exit
--

CREATE TABLE ci_employee_exit (
    exit_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    exit_date varchar(255) NOT NULL,
    exit_type_id INTEGER NOT NULL,
    exit_interview INTEGER NOT NULL,
    is_inactivate_account INTEGER NOT NULL,
    reason TEXT NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_employee_exit
--

TRUNCATE TABLE ci_employee_exit;
-- --------------------------------------------------------

--
-- Table structure for table ci_erp_company_settings
--

CREATE TABLE ci_erp_company_settings (
    setting_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    default_currency varchar(255) NOT NULL DEFAULT 'USD',
    default_currency_symbol varchar(100) NOT NULL DEFAULT 'USD',
    notification_position varchar(255) DEFAULT NULL,
    notification_close_btn varchar(255) DEFAULT NULL,
    notification_bar varchar(255) DEFAULT NULL,
    date_format_xi varchar(255) DEFAULT NULL,
    default_language varchar(200) NOT NULL DEFAULT 'en',
    system_timezone varchar(200) NOT NULL DEFAULT 'Asia/Bishkek',
    paypal_email varchar(100) DEFAULT NULL,
    paypal_sandbox varchar(10) DEFAULT NULL,
    paypal_active varchar(10) DEFAULT NULL,
    stripe_secret_key varchar(200) DEFAULT NULL,
    stripe_publishable_key varchar(200) DEFAULT NULL,
    stripe_active varchar(10) DEFAULT NULL,
    invoice_terms_condition text DEFAULT NULL,
    setup_modules text NOT NULL,
    header_background varchar(100) NOT NULL DEFAULT 'bg-dark',
    calendar_locale varchar(100) NOT NULL DEFAULT 'en',
    datepicker_locale varchar(100) NOT NULL DEFAULT 'en',
    login_page INTEGER NOT NULL,
    login_page_text text DEFAULT NULL,
    updated_at varchar(255) DEFAULT NULL
);


--
-- Truncate table before insert ci_erp_company_settings
--

TRUNCATE TABLE ci_erp_company_settings;
--
-- Dumping data for table ci_erp_company_settings
--

INSERT INTO ci_erp_company_settings (setting_id, company_id, default_currency, default_currency_symbol, notification_position, notification_close_btn, notification_bar, date_format_xi, default_language, system_timezone, paypal_email, paypal_sandbox, paypal_active, stripe_secret_key, stripe_publishable_key, stripe_active, invoice_terms_condition, setup_modules, header_background, calendar_locale, datepicker_locale, login_page, login_page_text, updated_at) VALUES
(1, 2, 'GBP', 'GBP', 'toast-top-center', '0', 'true', 'Y-m-d', 'en', 'America/New_York', 'paypal@example.com', 'yes', 'yes', 'stripe_secret_key', 'stripe_publishable_key', 'yes', 'lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt', 'a:9:{s:11:\"recruitment\";s:1:\"1\";s:6:\"travel\";s:1:\"1\";s:8:\"fmanager\";s:1:\"1\";s:8:\"orgchart\";s:1:\"1\";s:6:\"events\";s:1:\"1\";s:11:\"performance\";s:1:\"1\";s:5:\"award\";s:1:\"1\";s:8:\"training\";s:1:\"1\";s:9:\"inventory\";s:1:\"1\";}', 'bg-dark', 'en', 'en', 2, 'HRSALE provides you with a powerful and cost-effective HR platform to ensure you get the best from your employees and managers. HRSALE is a timely solution to upgrade and modernize your HR team to make it more efficient and consolidate your employee information into one intuitive HR system.', '15-05-2021 08:11:26');

-- --------------------------------------------------------

--
-- Table structure for table ci_erp_constants
--

CREATE TABLE ci_erp_constants (
    constants_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    type varchar(100) NOT NULL,
    category_name varchar(200) NOT NULL,
    field_one varchar(200) DEFAULT NULL,
    field_two varchar(200) DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_erp_constants
--

TRUNCATE TABLE ci_erp_constants;
--
-- Dumping data for table ci_erp_constants
--

INSERT INTO ci_erp_constants (constants_id, company_id, type, category_name, field_one, field_two, created_at) VALUES
(3, 81, 'company_type', 'Corporation', 'Null', 'Null', '09-05-2021 06:36:23'),
(4, 81, 'company_type', 'Exempt Organization', 'Null', 'Null', '09-05-2021 06:36:23'),
(5, 81, 'company_type', 'Partnership', 'Null', 'Null', '09-05-2021 06:36:23'),
(6, 81, 'company_type', 'Private Foundation', 'Null', 'Null', '09-05-2021 06:36:23'),
(7, 81, 'company_type', 'Limited Liability Company', 'Null', 'Null', '09-05-2021 06:36:23'),
(16, 81, 'religion', 'Agnosticism', 'Null', 'Null', '09-05-2021 06:36:23'),
(17, 81, 'religion', 'Atheism', 'Null', 'Null', '09-05-2021 06:36:23'),
(18, 81, 'religion', 'Baha''i', 'Null', 'Null', '09-05-2021 06:36:23'),
(19, 81, 'religion', 'Buddhism', 'Null', 'Null', '09-05-2021 06:36:23'),
(20, 81, 'religion', 'Christianity', 'Null', 'Null', '09-05-2021 06:36:23'),
(21, 81, 'religion', 'Humanism', 'Null', 'Null', '09-05-2021 06:36:23'),
(22, 81, 'religion', 'Hinduism', 'Null', 'Null', '09-05-2021 06:36:23'),
(23, 81, 'religion', 'Islam', 'Null', 'Null', '09-05-2021 06:36:23'),
(24, 81, 'religion', 'Jainism', 'Null', 'Null', '09-05-2021 06:36:23'),
(25, 81, 'religion', 'Judaism', 'Null', 'Null', '09-05-2021 06:36:23'),
(26, 81, 'religion', 'Sikhism', 'Null', 'Null', '09-05-2021 06:36:23'),
(27, 81, 'religion', 'Zoroastrianism', 'Null', 'Null', '09-05-2021 06:36:23'),
(93, 81, 'payment_method', 'Cash', '', '', '09-05-2021 06:36:23'),
(94, 81, 'payment_method', 'Paypal', '', '', '09-05-2021 06:36:23'),
(95, 81, 'payment_method', 'Bank', '', '', '09-05-2021 06:36:23'),
(98, 81, 'payment_method', 'Stripe', '', '', '09-05-2021 06:36:23'),
(99, 81, 'payment_method', 'Paystack', '', '', '09-05-2021 06:36:23'),
(100, 81, 'payment_method', 'Cheque', '', '', '09-05-2021 06:36:23'),
(101, 2, 'competencies', 'Leadership', 'Null', 'Null', '15-05-2021 05:37:16'),
(102, 2, 'competencies', 'Professional Impact', 'Null', 'Null', '15-05-2021 05:37:59'),
(103, 2, 'competencies', 'Oral Communication', 'Null', 'Null', '15-05-2021 05:38:48'),
(104, 2, 'competencies', 'Self Management', 'Null', 'Null', '15-05-2021 05:39:03'),
(105, 2, 'competencies', 'Team Work', 'Null', 'Null', '15-05-2021 05:39:45'),
(106, 2, 'competencies2', 'Allocating Resources', 'Null', 'Null', '15-05-2021 05:40:05'),
(107, 2, 'competencies2', 'Organizational Design', 'Null', 'Null', '15-05-2021 05:40:24'),
(108, 2, 'competencies2', 'Organizational Savvy', 'Null', 'Null', '15-05-2021 05:40:28'),
(109, 2, 'competencies2', 'Business Process', 'Null', 'Null', '15-05-2021 05:40:40'),
(110, 2, 'competencies2', 'Project Management', 'Null', 'Null', '15-05-2021 05:40:49'),
(111, 2, 'tax_type', 'Capital Gains', '10', 'percentage', '15-05-2021 02:14:32'),
(112, 2, 'tax_type', 'Value-Added Tax', '5', 'percentage', '15-05-2021 02:15:08'),
(113, 2, 'tax_type', 'Excise Taxes', '12', 'fixed', '15-05-2021 02:15:37'),
(114, 2, 'tax_type', 'Wealth Taxes', '8', 'percentage', '15-05-2021 02:16:02'),
(115, 2, 'tax_type', 'No Tax', '0', 'fixed', '15-05-2021 02:16:28'),
(116, 2, 'leave_type', 'Annual', '10', '1', '15-05-2021 03:23:35'),
(117, 2, 'leave_type', 'Sick', '5', '1', '15-05-2021 03:23:45'),
(118, 2, 'leave_type', 'Hospitalisation', '2', '1', '15-05-2021 03:23:56'),
(119, 2, 'leave_type', 'Maternity', '7', '1', '15-05-2021 03:24:09'),
(120, 2, 'leave_type', 'Paternity', '7', '1', '15-05-2021 03:24:19'),
(121, 2, 'leave_type', 'LOP', '10', '1', '15-05-2021 03:24:26'),
(122, 2, 'leave_type', 'Bereavement', '10', '1', '15-05-2021 03:27:02'),
(123, 2, 'leave_type', 'Compensatory', '5', '1', '15-05-2021 03:27:18'),
(124, 2, 'leave_type', 'Sabbatical', '7', '1', '15-05-2021 03:27:32'),
(125, 2, 'training_type', 'Technical', 'Null', 'Null', '15-05-2021 03:35:04'),
(126, 2, 'training_type', 'Advanced research skills', 'Null', 'Null', '15-05-2021 03:35:16'),
(127, 2, 'training_type', 'Strong communication skills', 'Null', 'Null', '15-05-2021 03:35:25'),
(128, 2, 'training_type', 'Adaptability skills', 'Null', 'Null', '15-05-2021 03:35:33'),
(129, 2, 'training_type', 'Social media', 'Null', 'Null', '15-05-2021 03:35:47'),
(130, 2, 'training_type', 'Enthusiasm for Learning', 'Null', 'Null', '15-05-2021 03:36:01'),
(131, 2, 'training_type', 'Soft Skills', 'Null', 'Null', '15-05-2021 03:36:09'),
(132, 2, 'training_type', 'Professional Training', 'Null', 'Null', '15-05-2021 03:36:16'),
(133, 2, 'training_type', 'Team Training', 'Null', 'Null', '15-05-2021 03:36:23'),
(134, 2, 'goal_type', 'Revamp Employee experience', 'Null', 'Null', '15-05-2021 03:42:46'),
(135, 2, 'goal_type', 'Talent Retention', 'Null', 'Null', '15-05-2021 03:42:55'),
(136, 2, 'goal_type', 'Talent Acquisition', 'Null', 'Null', '15-05-2021 03:43:09'),
(137, 2, 'goal_type', 'Strengthen the Feedback Structure', 'Null', 'Null', '15-05-2021 04:16:26'),
(138, 2, 'goal_type', 'Boost Company culture', 'Null', 'Null', '15-05-2021 04:17:47'),
(139, 2, 'warning_type', 'Written Notice', 'Null', 'Null', '15-05-2021 04:33:13'),
(140, 2, 'warning_type', 'Letter of Written Reprimand', 'Null', 'Null', '15-05-2021 04:33:25'),
(141, 2, 'warning_type', 'Letter of Suspension', 'Null', 'Null', '15-05-2021 04:33:33'),
(142, 2, 'warning_type', 'Disciplinary Demotion', 'Null', 'Null', '15-05-2021 04:33:40'),
(143, 2, 'warning_type', 'Letter of Discharge', 'Null', 'Null', '15-05-2021 04:34:06'),
(144, 2, 'warning_type', 'Reassignment', 'Null', 'Null', '15-05-2021 04:34:13'),
(145, 2, 'warning_type', 'Non-discrimination', 'Null', 'Null', '15-05-2021 04:34:19'),
(146, 2, 'warning_type', 'Confidentiality', 'Null', 'Null', '15-05-2021 04:34:26'),
(147, 2, 'expense_type', 'Fuel Expense', 'Null', 'Null', '15-05-2021 06:04:48'),
(148, 2, 'expense_type', 'Advertising', 'Null', 'Null', '15-05-2021 06:05:11'),
(149, 2, 'expense_type', 'Salaries Expense', 'Null', 'Null', '15-05-2021 06:05:30'),
(150, 2, 'expense_type', 'Warranty Expense', 'Null', 'Null', '15-05-2021 06:05:58'),
(151, 2, 'expense_type', 'Other Expense', 'Null', 'Null', '15-05-2021 06:06:14'),
(152, 2, 'expense_type', 'Insurance', 'Null', 'Null', '15-05-2021 06:06:27'),
(153, 2, 'expense_type', 'Miscellaneous', 'Null', 'Null', '15-05-2021 06:06:51'),
(154, 2, 'expense_type', 'Payroll Tax', 'Null', 'Null', '15-05-2021 06:07:25'),
(155, 2, 'expense_type', 'Utilities', 'Null', 'Null', '15-05-2021 06:08:00'),
(156, 2, 'income_type', 'Capital Stock', 'Null', 'Null', '15-05-2021 06:09:03'),
(157, 2, 'income_type', 'Cash Over', 'Null', 'Null', '15-05-2021 06:09:15'),
(158, 2, 'income_type', 'Common Stock', 'Null', 'Null', '15-05-2021 06:09:28'),
(159, 2, 'income_type', 'Insurance Payable', 'Null', 'Null', '15-05-2021 06:11:42'),
(160, 2, 'income_type', 'Interest Income', 'Null', 'Null', '15-05-2021 06:11:53'),
(161, 2, 'expense_type', 'Interest Expense', 'Null', 'Null', '15-05-2021 06:12:12'),
(162, 2, 'income_type', 'Investment Income', 'Null', 'Null', '15-05-2021 06:12:55'),
(163, 2, 'income_type', 'Retained Earnings', 'Null', 'Null', '15-05-2021 06:13:39'),
(164, 2, 'income_type', 'Sales', 'Null', 'Null', '15-05-2021 06:14:27'),
(165, 2, 'income_type', 'Other Income', 'Null', 'Null', '15-05-2021 06:15:47');

-- --------------------------------------------------------

--
-- Table structure for table ci_erp_settings
--

CREATE TABLE ci_erp_settings (
    setting_id SERIAL PRIMARY KEY,
    application_name varchar(255) NOT NULL,
    company_name varchar(100) DEFAULT NULL,
    trading_name varchar(100) DEFAULT NULL,
    registration_no varchar(100) DEFAULT NULL,
    government_tax varchar(100) DEFAULT NULL,
    company_type_id INTEGER NOT NULL,
    email varchar(200) DEFAULT NULL,
    contact_number varchar(255) DEFAULT NULL,
    country INTEGER NOT NULL DEFAULT 0,
    address_1 text DEFAULT NULL,
    address_2 text DEFAULT NULL,
    city varchar(200) DEFAULT NULL,
    zipcode varchar(200) DEFAULT NULL,
    state varchar(200) DEFAULT NULL,
    default_currency varchar(255) NOT NULL DEFAULT 'USD',
    is_ssl_available varchar(11) NOT NULL DEFAULT 'on',
    currency_converter TEXT DEFAULT NULL,
    notification_position varchar(255) NOT NULL,
    notification_close_btn varchar(255) NOT NULL,
    notification_bar varchar(255) NOT NULL,
    date_format_xi varchar(255) NOT NULL,
    enable_email_notification varchar(255) NOT NULL,
    email_type varchar(100) NOT NULL,
    logo varchar(200) NOT NULL,
    favicon varchar(200) NOT NULL,
    frontend_logo varchar(200) NOT NULL,
    other_logo varchar(255) DEFAULT NULL,
    animation_effect varchar(255) NOT NULL,
    animation_effect_modal varchar(255) NOT NULL,
    animation_effect_topmenu varchar(255) NOT NULL,
    default_language varchar(200) NOT NULL DEFAULT 'en',
    system_timezone varchar(200) NOT NULL DEFAULT 'Asia/Bishkek',
    paypal_email varchar(100) NOT NULL,
    paypal_sandbox varchar(10) NOT NULL,
    paypal_active varchar(10) NOT NULL,
    stripe_secret_key varchar(200) NOT NULL,
    stripe_publishable_key varchar(200) NOT NULL,
    stripe_active varchar(10) NOT NULL,
    online_payment_account INTEGER NOT NULL,
    invoice_terms_condition text DEFAULT NULL,
    enable_sms_notification INTEGER NOT NULL,
    sms_from varchar(200) NOT NULL,
    sms_service_plan_id text DEFAULT NULL,
    sms_bearer_token text DEFAULT NULL,
    auth_background varchar(255) DEFAULT NULL,
    hr_version varchar(200) NOT NULL,
    hr_release_date varchar(100) NOT NULL,
    updated_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_erp_settings
--

TRUNCATE TABLE ci_erp_settings;
--
-- Dumping data for table ci_erp_settings
--

INSERT INTO ci_erp_settings (setting_id, application_name, company_name, trading_name, registration_no, government_tax, company_type_id, email, contact_number, country, address_1, address_2, city, zipcode, state, default_currency, is_ssl_available, currency_converter, notification_position, notification_close_btn, notification_bar, date_format_xi, enable_email_notification, email_type, logo, favicon, frontend_logo, other_logo, animation_effect, animation_effect_modal, animation_effect_topmenu, default_language, system_timezone, paypal_email, paypal_sandbox, paypal_active, stripe_secret_key, stripe_publishable_key, stripe_active, online_payment_account, invoice_terms_condition, enable_sms_notification, sms_from, sms_service_plan_id, sms_bearer_token, auth_background, hr_version, hr_release_date, updated_at) VALUES
(1, 'HRSALE', 'HRSALE', 'LG-859636', 'RG-741526333', 'Tx-8593214014', 3, 'info@timehrm.com', '+21258-9636', 119, '9856 Mandani Road', 'Columbia YH POL', 'Missouri', '45896', 'Missouri', 'USD', '0', 'a:160:{s:3:\"USD\";s:1:\"1\";s:3:\"AED\";s:4:\"3.67\";s:3:\"AFN\";s:5:\"78.37\";s:3:\"ALL\";s:6:\"101.16\";s:3:\"AMD\";s:6:\"522.46\";s:3:\"ANG\";s:4:\"1.79\";s:3:\"AOA\";s:5:\"649.6\";s:3:\"ARS\";s:5:\"93.96\";s:3:\"AUD\";s:4:\"1.29\";s:3:\"AWG\";s:4:\"1.79\";s:3:\"AZN\";s:3:\"1.7\";s:3:\"BAM\";s:4:\"1.61\";s:3:\"BBD\";s:1:\"2\";s:3:\"BDT\";s:5:\"84.85\";s:3:\"BGN\";s:4:\"1.61\";s:3:\"BHD\";s:5:\"0.376\";s:3:\"BIF\";s:7:\"1957.01\";s:3:\"BMD\";s:1:\"1\";s:3:\"BND\";s:4:\"1.33\";s:3:\"BOB\";s:4:\"6.87\";s:3:\"BRL\";s:4:\"5.29\";s:3:\"BSD\";s:1:\"1\";s:3:\"BTN\";s:5:\"73.36\";s:3:\"BWP\";s:4:\"10.8\";s:3:\"BYN\";s:4:\"2.53\";s:3:\"BZD\";s:1:\"2\";s:3:\"CAD\";s:4:\"1.21\";s:3:\"CDF\";s:7:\"1988.03\";s:3:\"CHF\";s:5:\"0.903\";s:3:\"CLP\";s:5:\"707.8\";s:3:\"CNY\";s:4:\"6.44\";s:3:\"COP\";s:7:\"3711.94\";s:3:\"CRC\";s:6:\"612.38\";s:3:\"CUC\";s:1:\"1\";s:3:\"CUP\";s:5:\"25.75\";s:3:\"CVE\";s:5:\"90.86\";s:3:\"CZK\";s:5:\"21.02\";s:3:\"DJF\";s:6:\"177.72\";s:3:\"DKK\";s:4:\"6.15\";s:3:\"DOP\";s:5:\"57.01\";s:3:\"DZD\";s:6:\"133.47\";s:3:\"EGP\";s:5:\"15.64\";s:3:\"ERN\";s:2:\"15\";s:3:\"ETB\";s:5:\"42.71\";s:3:\"EUR\";s:5:\"0.824\";s:3:\"FJD\";s:4:\"2.04\";s:3:\"FKP\";s:4:\"0.71\";s:3:\"FOK\";s:4:\"6.15\";s:3:\"GBP\";s:4:\"0.71\";s:3:\"GEL\";s:4:\"3.42\";s:3:\"GGP\";s:4:\"0.71\";s:3:\"GHS\";s:4:\"5.76\";s:3:\"GIP\";s:4:\"0.71\";s:3:\"GMD\";s:5:\"51.95\";s:3:\"GNF\";s:7:\"9856.45\";s:3:\"GTQ\";s:4:\"7.69\";s:3:\"GYD\";s:6:\"213.16\";s:3:\"HKD\";s:4:\"7.77\";s:3:\"HNL\";s:2:\"24\";s:3:\"HRK\";s:4:\"6.21\";s:3:\"HTG\";s:5:\"87.66\";s:3:\"HUF\";s:6:\"293.53\";s:3:\"IDR\";s:8:\"14265.53\";s:3:\"ILS\";s:4:\"3.28\";s:3:\"IMP\";s:4:\"0.71\";s:3:\"INR\";s:5:\"73.36\";s:3:\"IQD\";s:7:\"1458.51\";s:3:\"IRR\";s:8:\"42064.89\";s:3:\"ISK\";s:6:\"124.36\";s:3:\"JMD\";s:6:\"150.88\";s:3:\"JOD\";s:5:\"0.709\";s:3:\"JPY\";s:6:\"109.38\";s:3:\"KES\";s:6:\"107.18\";s:3:\"KGS\";s:5:\"84.68\";s:3:\"KHR\";s:7:\"4066.72\";s:3:\"KID\";s:4:\"1.29\";s:3:\"KMF\";s:5:\"405.4\";s:3:\"KRW\";s:7:\"1128.24\";s:3:\"KWD\";s:3:\"0.3\";s:3:\"KYD\";s:5:\"0.833\";s:3:\"KZT\";s:6:\"427.47\";s:3:\"LAK\";s:7:\"9409.64\";s:3:\"LBP\";s:6:\"1507.5\";s:3:\"LKR\";s:6:\"196.68\";s:3:\"LRD\";s:6:\"171.74\";s:3:\"LSL\";s:4:\"14.1\";s:3:\"LYD\";s:4:\"4.46\";s:3:\"MAD\";s:4:\"8.85\";s:3:\"MDL\";s:5:\"17.75\";s:3:\"MGA\";s:7:\"3755.12\";s:3:\"MKD\";s:5:\"50.76\";s:3:\"MMK\";s:7:\"1558.16\";s:3:\"MNT\";s:7:\"2846.09\";s:3:\"MOP\";s:1:\"8\";s:3:\"MRU\";s:5:\"35.84\";s:3:\"MUR\";s:4:\"40.4\";s:3:\"MVR\";s:5:\"15.33\";s:3:\"MWK\";s:6:\"796.85\";s:3:\"MXN\";s:5:\"19.87\";s:3:\"MYR\";s:4:\"4.12\";s:3:\"MZN\";s:5:\"58.75\";s:3:\"NAD\";s:4:\"14.1\";s:3:\"NGN\";s:6:\"422.32\";s:3:\"NIO\";s:5:\"35.03\";s:3:\"NOK\";s:4:\"8.26\";s:3:\"NPR\";s:6:\"117.38\";s:3:\"NZD\";s:4:\"1.38\";s:3:\"OMR\";s:5:\"0.384\";s:3:\"PAB\";s:1:\"1\";s:3:\"PEN\";s:4:\"3.67\";s:3:\"PGK\";s:4:\"3.51\";s:3:\"PHP\";s:4:\"47.8\";s:3:\"PKR\";s:6:\"151.64\";s:3:\"PLN\";s:4:\"3.73\";s:3:\"PYG\";s:7:\"6556.73\";s:3:\"QAR\";s:4:\"3.64\";s:3:\"RON\";s:4:\"4.06\";s:3:\"RSD\";s:5:\"97.23\";s:3:\"RUB\";s:5:\"73.95\";s:3:\"RWF\";s:6:\"999.71\";s:3:\"SAR\";s:4:\"3.75\";s:3:\"SBD\";s:4:\"7.86\";s:3:\"SCR\";s:5:\"15.44\";s:3:\"SDG\";s:6:\"398.45\";s:3:\"SEK\";s:4:\"8.36\";s:3:\"SGD\";s:4:\"1.33\";s:3:\"SHP\";s:4:\"0.71\";s:3:\"SLL\";s:8:\"10261.51\";s:3:\"SOS\";s:6:\"578.45\";s:3:\"SRD\";s:5:\"14.15\";s:3:\"SSP\";s:6:\"177.64\";s:3:\"STN\";s:5:\"20.19\";s:3:\"SYP\";s:7:\"1286.59\";s:3:\"SZL\";s:4:\"14.1\";s:3:\"THB\";s:5:\"31.37\";s:3:\"TJS\";s:5:\"11.31\";s:3:\"TMT\";s:3:\"3.5\";s:3:\"TND\";s:4:\"2.72\";s:3:\"TOP\";s:4:\"2.23\";s:3:\"TRY\";s:4:\"8.46\";s:3:\"TTD\";s:3:\"6.8\";s:3:\"TVD\";s:4:\"1.29\";s:3:\"TWD\";s:5:\"27.93\";s:3:\"TZS\";s:7:\"2314.62\";s:3:\"UAH\";s:5:\"27.62\";s:3:\"UGX\";s:7:\"3529.82\";s:3:\"UYU\";s:5:\"44.13\";s:3:\"UZS\";s:8:\"10454.23\";s:3:\"VES\";s:10:\"2959224.82\";s:3:\"VND\";s:8:\"23123.31\";s:3:\"VUV\";s:6:\"107.04\";s:3:\"WST\";s:4:\"2.51\";s:3:\"XAF\";s:6:\"540.53\";s:3:\"XCD\";s:3:\"2.7\";s:3:\"XDR\";s:5:\"0.695\";s:3:\"XOF\";s:6:\"540.53\";s:3:\"XPF\";s:5:\"98.33\";s:3:\"YER\";s:6:\"249.75\";s:3:\"ZAR\";s:4:\"14.1\";s:3:\"ZMW\";s:5:\"22.42\";}', 'toast-top-center', '0', 'true', 'Y-m-d', '0', 'codeigniter', 'hrsale-logo-white.png', 'favicon_1520722747.png', 'logo.png', 'signin_logo_1553391482.png', 'fadeInDown', 'tada', 'tada', 'en', 'Asia/Bishkek', 'your.paypal.email@domain.com', 'yes', 'no', 'sk_test_REPLACE_WITH_YOUR_KEY', 'pk_test_REPLACE_WITH_YOUR_KEY', 'yes', 2, 'lorem ipsum dolor sit', 0, '', NULL, NULL, '4910284', '1.0.0', '2021-05-09', '09-05-2021 06:36:23');

-- --------------------------------------------------------

--
-- Table structure for table ci_erp_users
--

CREATE TABLE ci_erp_users (
    user_id SERIAL PRIMARY KEY,
    user_role_id INTEGER DEFAULT NULL,
    user_type varchar(50) NOT NULL,
    company_id INTEGER NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    username varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    company_name varchar(100) DEFAULT NULL,
    trading_name varchar(100) DEFAULT NULL,
    registration_no varchar(100) DEFAULT NULL,
    government_tax varchar(100) DEFAULT NULL,
    company_type_id INTEGER DEFAULT NULL,
    profile_photo varchar(255) NOT NULL,
    contact_number varchar(255) DEFAULT NULL,
    gender varchar(20) NOT NULL,
    address_1 text DEFAULT NULL,
    address_2 text DEFAULT NULL,
    city varchar(255) DEFAULT NULL,
    state varchar(255) DEFAULT NULL,
    zipcode varchar(255) DEFAULT NULL,
    country INTEGER DEFAULT NULL,
    last_login_date varchar(255) DEFAULT NULL,
    last_logout_date varchar(200) DEFAULT NULL,
    last_login_ip varchar(255) DEFAULT NULL,
    is_logged_in INTEGER DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_erp_users
--

TRUNCATE TABLE ci_erp_users;
--
-- Dumping data for table ci_erp_users
--

INSERT INTO ci_erp_users (user_id, user_role_id, user_type, company_id, first_name, last_name, email, username, password, company_name, trading_name, registration_no, government_tax, company_type_id, profile_photo, contact_number, gender, address_1, address_2, city, state, zipcode, country, last_login_date, last_logout_date, last_login_ip, is_logged_in, is_active, created_at) VALUES
(2, 0, 'company', 0, 'Frances', 'Burns', 'kelly.flynn@hrsale.com', 'kelly.flynn', '$2y$12$8qh6dbDeYQalBNHOcdmv1ePwr4UIyr1wD0MToqCP6QLYi8mO7gX9a', 'HRSALE', 'TRD-9853142', 'RG-153974520', 'TX-74521583', 6, 'a-sm.jpg', '1234567890', '1', 'Sadovnicheskaya embankment 79', 'MD 20815', 'Moscow', 'Moscow', '20834', 182, '04-08-2021 12:17:50', '04-08-2021 13:18:39', '::1', 0, 1, '15-05-2021 08:11:26');

-- --------------------------------------------------------

--
-- Table structure for table ci_erp_users_details
--

CREATE TABLE ci_erp_users_details (
    staff_details_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    employee_id varchar(255) NOT NULL,
    department_id INTEGER NOT NULL,
    designation_id INTEGER NOT NULL,
    office_shift_id INTEGER NOT NULL,
    basic_salary NUMERIC(12,2) NOT NULL,
    hourly_rate NUMERIC(12,2) NOT NULL,
    salay_type INTEGER NOT NULL,
    leave_categories varchar(255) NOT NULL DEFAULT 'all',
    role_description TEXT DEFAULT NULL,
    date_of_joining varchar(200) DEFAULT NULL,
    date_of_leaving varchar(200) DEFAULT NULL,
    date_of_birth varchar(200) DEFAULT NULL,
    marital_status INTEGER DEFAULT NULL,
    religion_id INTEGER DEFAULT NULL,
    blood_group varchar(200) DEFAULT NULL,
    citizenship_id INTEGER DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    experience INTEGER DEFAULT NULL,
    fb_profile TEXT DEFAULT NULL,
    twitter_profile TEXT DEFAULT NULL,
    gplus_profile TEXT DEFAULT NULL,
    linkedin_profile TEXT DEFAULT NULL,
    account_title varchar(255) DEFAULT NULL,
    account_number varchar(255) DEFAULT NULL,
    bank_name varchar(255) DEFAULT NULL,
    iban varchar(255) DEFAULT NULL,
    swift_code varchar(255) DEFAULT NULL,
    bank_branch TEXT DEFAULT NULL,
    contact_full_name varchar(200) DEFAULT NULL,
    contact_phone_no varchar(200) DEFAULT NULL,
    contact_email varchar(200) DEFAULT NULL,
    contact_address TEXT DEFAULT NULL,
    created_at varchar(200) DEFAULT NULL
);


--
-- Truncate table before insert ci_erp_users_details
--

TRUNCATE TABLE ci_erp_users_details;
-- --------------------------------------------------------

--
-- Table structure for table ci_erp_users_role
--

CREATE TABLE ci_erp_users_role (
    role_id SERIAL PRIMARY KEY,
    role_name varchar(200) DEFAULT NULL,
    role_access varchar(200) DEFAULT NULL,
    role_resources text DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_erp_users_role
--

TRUNCATE TABLE ci_erp_users_role;
-- --------------------------------------------------------

--
-- Table structure for table ci_estimates
--

CREATE TABLE ci_estimates (
    estimate_id SERIAL PRIMARY KEY,
    estimate_number varchar(255) NOT NULL,
    company_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    estimate_month varchar(255) DEFAULT NULL,
    estimate_date varchar(255) NOT NULL,
    estimate_due_date varchar(255) NOT NULL,
    sub_total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_type varchar(11) NOT NULL,
    discount_figure NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_tax NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    tax_type varchar(100) DEFAULT NULL,
    total_discount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    grand_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    estimate_note TEXT NOT NULL,
    status SMALLINT NOT NULL,
    payment_method INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_estimates
--

TRUNCATE TABLE ci_estimates;
-- --------------------------------------------------------

--
-- Table structure for table ci_estimates_items
--

CREATE TABLE ci_estimates_items (
    estimate_item_id SERIAL PRIMARY KEY,
    estimate_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    item_name varchar(255) NOT NULL,
    item_qty varchar(255) NOT NULL,
    item_unit_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    item_sub_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_estimates_items
--

TRUNCATE TABLE ci_estimates_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_events
--

CREATE TABLE ci_events (
    event_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id varchar(255) DEFAULT NULL,
    event_title varchar(255) NOT NULL,
    event_date varchar(255) NOT NULL,
    event_time varchar(255) NOT NULL,
    event_note TEXT NOT NULL,
    event_color varchar(200) NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_events
--

TRUNCATE TABLE ci_events;
-- --------------------------------------------------------

--
-- Table structure for table ci_finance_accounts
--

CREATE TABLE ci_finance_accounts (
    account_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    account_name varchar(255) NOT NULL,
    account_balance NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    account_opening_balance NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    account_number varchar(255) NOT NULL,
    branch_code varchar(255) NOT NULL,
    bank_branch text NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_finance_accounts
--

TRUNCATE TABLE ci_finance_accounts;
-- --------------------------------------------------------

--
-- Table structure for table ci_finance_entity
--

CREATE TABLE ci_finance_entity (
    entity_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    name varchar(100) NOT NULL,
    contact_number varchar(100) NOT NULL,
    type varchar(15) NOT NULL,
    created_at varchar(100) NOT NULL
);


--
-- Truncate table before insert ci_finance_entity
--

TRUNCATE TABLE ci_finance_entity;
-- --------------------------------------------------------

--
-- Table structure for table ci_finance_membership_invoices
--

CREATE TABLE ci_finance_membership_invoices (
    membership_invoice_id SERIAL PRIMARY KEY,
    invoice_id varchar(50) DEFAULT NULL,
    company_id INTEGER NOT NULL,
    membership_id INTEGER NOT NULL,
    subscription_id varchar(50) DEFAULT NULL,
    membership_type varchar(200) NOT NULL,
    subscription varchar(200) NOT NULL,
    invoice_month varchar(255) DEFAULT NULL,
    membership_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payment_method varchar(200) NOT NULL,
    transaction_date varchar(200) NOT NULL,
    description TEXT NOT NULL,
    receipt_url TEXT DEFAULT NULL,
    source_info varchar(10) DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_finance_membership_invoices
--

TRUNCATE TABLE ci_finance_membership_invoices;
--
-- Dumping data for table ci_finance_membership_invoices
--

INSERT INTO ci_finance_membership_invoices (membership_invoice_id, invoice_id, company_id, membership_id, subscription_id, membership_type, subscription, invoice_month, membership_price, payment_method, transaction_date, description, receipt_url, source_info, created_at) VALUES
(4, 'txn_1IrrDtJck1huBCXGA8vOl02B', 2, 6, '100585963', 'Pro Plan', '2', '2021-05', '59.00', 'Stripe', '2021-05-16 05:10:07', 'Free server unlimited approx 255k+ Premium collection', 'stripe.com', 'visa', '2021-05-16 05:10:07');

-- --------------------------------------------------------

--
-- Table structure for table ci_finance_transactions
--

CREATE TABLE ci_finance_transactions (
    transaction_id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    transaction_date varchar(255) NOT NULL,
    transaction_type varchar(100) NOT NULL,
    entity_id INTEGER NOT NULL,
    entity_type varchar(100) DEFAULT NULL,
    entity_category_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    dr_cr VARCHAR(50) NOT NULL CHECK (dr_cr IN ('dr','cr')),
    payment_method_id INTEGER NOT NULL,
    reference varchar(100) DEFAULT NULL,
    attachment_file varchar(100) DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_finance_transactions
--

TRUNCATE TABLE ci_finance_transactions;
-- --------------------------------------------------------

--
-- Table structure for table ci_holidays
--

CREATE TABLE ci_holidays (
    holiday_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    event_name varchar(200) NOT NULL,
    description TEXT NOT NULL,
    start_date varchar(200) NOT NULL,
    end_date varchar(200) NOT NULL,
    is_publish SMALLINT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_holidays
--

TRUNCATE TABLE ci_holidays;
-- --------------------------------------------------------

--
-- Table structure for table ci_invoices
--

CREATE TABLE ci_invoices (
    invoice_id SERIAL PRIMARY KEY,
    invoice_number varchar(255) NOT NULL,
    company_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    invoice_month varchar(255) DEFAULT NULL,
    invoice_date varchar(255) NOT NULL,
    invoice_due_date varchar(255) NOT NULL,
    sub_total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_type varchar(11) NOT NULL,
    discount_figure NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_tax NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    tax_type varchar(100) DEFAULT NULL,
    total_discount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    grand_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    invoice_note TEXT NOT NULL,
    status SMALLINT NOT NULL,
    payment_method INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_invoices
--

TRUNCATE TABLE ci_invoices;
-- --------------------------------------------------------

--
-- Table structure for table ci_invoices_items
--

CREATE TABLE ci_invoices_items (
    invoice_item_id SERIAL PRIMARY KEY,
    invoice_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    item_name varchar(255) NOT NULL,
    item_qty varchar(255) NOT NULL,
    item_unit_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    item_sub_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_invoices_items
--

TRUNCATE TABLE ci_invoices_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_languages
--

CREATE TABLE ci_languages (
    language_id SERIAL PRIMARY KEY,
    language_name varchar(255) NOT NULL,
    language_code varchar(255) NOT NULL,
    language_flag varchar(255) NOT NULL,
    is_active INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_languages
--

TRUNCATE TABLE ci_languages;
--
-- Dumping data for table ci_languages
--

INSERT INTO ci_languages (language_id, language_name, language_code, language_flag, is_active, created_at) VALUES
(1, 'English', 'en', 'en.gif', 1, '09-05-2021 06:36:23'),
(3, 'Russian', 'ru', 'ru.gif', 1, '14-05-2021 08:22:21'),
(4, 'Dutch', 'nl', 'nl.gif', 1, '14-05-2021 09:39:11'),
(5, 'Portuguese', 'br', 'br.gif', 1, '15-05-2021 12:28:41'),
(6, 'Vietnamese', 'vn', 'vn.gif', 1, '15-05-2021 12:29:04'),
(7, 'Spanish', 'es', 'es.gif', 1, '15-05-2021 12:30:13'),
(8, 'Italiano', 'it', 'it.gif', 1, '15-05-2021 12:30:54'),
(9, 'Turkish', 'tr', 'tr.gif', 1, '15-05-2021 12:31:21'),
(10, 'French', 'fr', 'fr.gif', 1, '15-05-2021 12:31:39'),
(11, 'Chinese', 'cn', 'cn.gif', 1, '15-05-2021 12:31:59');

-- --------------------------------------------------------

--
-- Table structure for table ci_leads
--

CREATE TABLE ci_leads (
    lead_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    first_name varchar(255) DEFAULT NULL,
    last_name varchar(255) DEFAULT NULL,
    email varchar(255) DEFAULT NULL,
    profile_photo varchar(255) DEFAULT NULL,
    contact_number varchar(255) DEFAULT NULL,
    gender INTEGER NOT NULL,
    address_1 text DEFAULT NULL,
    address_2 text DEFAULT NULL,
    city varchar(255) DEFAULT NULL,
    state varchar(255) DEFAULT NULL,
    zipcode varchar(255) DEFAULT NULL,
    country INTEGER NOT NULL,
    status INTEGER NOT NULL,
    created_at varchar(255) DEFAULT NULL
);


--
-- Truncate table before insert ci_leads
--

TRUNCATE TABLE ci_leads;
-- --------------------------------------------------------

--
-- Table structure for table ci_leads_followup
--

CREATE TABLE ci_leads_followup (
    followup_id SERIAL PRIMARY KEY,
    lead_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    next_followup varchar(255) NOT NULL,
    description text NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_leads_followup
--

TRUNCATE TABLE ci_leads_followup;
-- --------------------------------------------------------

--
-- Table structure for table ci_leave_applications
--

CREATE TABLE ci_leave_applications (
    leave_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    leave_type_id INTEGER NOT NULL,
    from_date varchar(200) NOT NULL,
    to_date varchar(200) NOT NULL,
    reason TEXT NOT NULL,
    remarks TEXT DEFAULT NULL,
    status SMALLINT NOT NULL DEFAULT 1,
    is_half_day SMALLINT DEFAULT NULL,
    leave_attachment varchar(255) DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_leave_applications
--

TRUNCATE TABLE ci_leave_applications;
-- --------------------------------------------------------

--
-- Table structure for table ci_meetings
--

CREATE TABLE ci_meetings (
    meeting_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id varchar(255) DEFAULT NULL,
    meeting_title varchar(255) NOT NULL,
    meeting_date varchar(255) NOT NULL,
    meeting_time varchar(255) NOT NULL,
    meeting_room varchar(255) NOT NULL,
    meeting_note TEXT NOT NULL,
    meeting_color varchar(200) NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_meetings
--

TRUNCATE TABLE ci_meetings;
-- --------------------------------------------------------

--
-- Table structure for table ci_membership
--

CREATE TABLE ci_membership (
    membership_id SERIAL PRIMARY KEY,
    subscription_id varchar(100) DEFAULT NULL,
    membership_type varchar(200) NOT NULL,
    price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    plan_duration INTEGER NOT NULL,
    total_employees INTEGER NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_membership
--

TRUNCATE TABLE ci_membership;
--
-- Dumping data for table ci_membership
--

INSERT INTO ci_membership (membership_id, subscription_id, membership_type, price, plan_duration, total_employees, description, created_at) VALUES
(6, '100585963', 'Pro Plan', '59.00', 2, 10, 'Free server unlimited approx 255k+ Premium collection', '09-05-2021 06:36:23');

-- --------------------------------------------------------

--
-- Table structure for table ci_module_attributes
--

CREATE TABLE ci_module_attributes (
    custom_field_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    attribute varchar(255) NOT NULL,
    attribute_label varchar(255) NOT NULL,
    attribute_type varchar(255) NOT NULL,
    col_width varchar(100) DEFAULT NULL,
    validation INTEGER NOT NULL,
    priority INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_module_attributes
--

TRUNCATE TABLE ci_module_attributes;
-- --------------------------------------------------------

--
-- Table structure for table ci_module_attributes_select_value
--

CREATE TABLE ci_module_attributes_select_value (
    attributes_select_value_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    custom_field_id INTEGER NOT NULL,
    select_label varchar(255) DEFAULT NULL
);


--
-- Truncate table before insert ci_module_attributes_select_value
--

TRUNCATE TABLE ci_module_attributes_select_value;
-- --------------------------------------------------------

--
-- Table structure for table ci_module_attributes_values
--

CREATE TABLE ci_module_attributes_values (
    attributes_value_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    module_attributes_id INTEGER NOT NULL,
    attribute_value text DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_module_attributes_values
--

TRUNCATE TABLE ci_module_attributes_values;
-- --------------------------------------------------------

--
-- Table structure for table ci_office_shifts
--

CREATE TABLE ci_office_shifts (
    office_shift_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    shift_name varchar(255) NOT NULL,
    monday_in_time varchar(222) NOT NULL,
    monday_out_time varchar(222) NOT NULL,
    tuesday_in_time varchar(222) NOT NULL,
    tuesday_out_time varchar(222) NOT NULL,
    wednesday_in_time varchar(222) NOT NULL,
    wednesday_out_time varchar(222) NOT NULL,
    thursday_in_time varchar(222) NOT NULL,
    thursday_out_time varchar(222) NOT NULL,
    friday_in_time varchar(222) NOT NULL,
    friday_out_time varchar(222) NOT NULL,
    saturday_in_time varchar(222) NOT NULL,
    saturday_out_time varchar(222) NOT NULL,
    sunday_in_time varchar(222) NOT NULL,
    sunday_out_time varchar(222) NOT NULL,
    created_at varchar(222) NOT NULL
);


--
-- Truncate table before insert ci_office_shifts
--

TRUNCATE TABLE ci_office_shifts;
-- --------------------------------------------------------

--
-- Table structure for table ci_official_documents
--

CREATE TABLE ci_official_documents (
    document_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    license_name varchar(255) NOT NULL,
    document_type varchar(255) NOT NULL,
    license_no varchar(200) DEFAULT NULL,
    expiry_date varchar(200) DEFAULT NULL,
    document_file varchar(255) NOT NULL,
    created_at varchar(200) DEFAULT NULL
);


--
-- Truncate table before insert ci_official_documents
--

TRUNCATE TABLE ci_official_documents;
-- --------------------------------------------------------

--
-- Table structure for table ci_payslips
--

CREATE TABLE ci_payslips (
    payslip_id SERIAL PRIMARY KEY,
    payslip_key varchar(200) NOT NULL,
    company_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    salary_month varchar(200) NOT NULL,
    wages_type INTEGER NOT NULL,
    payslip_type varchar(50) NOT NULL,
    basic_salary NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    daily_wages NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    hours_worked varchar(50) NOT NULL DEFAULT '0',
    total_allowances NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_commissions NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_statutory_deductions NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_other_payments NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    net_salary NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payment_method INTEGER NOT NULL,
    pay_comments TEXT NOT NULL,
    is_payment INTEGER NOT NULL,
    year_to_date varchar(200) NOT NULL,
    is_advance_salary_deduct INTEGER NOT NULL,
    advance_salary_amount NUMERIC(12,2) DEFAULT NULL,
    is_loan_deduct INTEGER NOT NULL,
    loan_amount NUMERIC(12,2) NOT NULL,
    status INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_payslips
--

TRUNCATE TABLE ci_payslips;
-- --------------------------------------------------------

--
-- Table structure for table ci_payslip_allowances
--

CREATE TABLE ci_payslip_allowances (
    payslip_allowances_id SERIAL PRIMARY KEY,
    payslip_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    is_taxable INTEGER NOT NULL,
    is_fixed INTEGER NOT NULL,
    pay_title varchar(200) NOT NULL,
    pay_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    salary_month varchar(200) NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_payslip_allowances
--

TRUNCATE TABLE ci_payslip_allowances;
-- --------------------------------------------------------

--
-- Table structure for table ci_payslip_commissions
--

CREATE TABLE ci_payslip_commissions (
    payslip_commissions_id SERIAL PRIMARY KEY,
    payslip_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    is_taxable INTEGER NOT NULL,
    is_fixed INTEGER NOT NULL,
    pay_title varchar(200) NOT NULL,
    pay_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    salary_month varchar(200) NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_payslip_commissions
--

TRUNCATE TABLE ci_payslip_commissions;
-- --------------------------------------------------------

--
-- Table structure for table ci_payslip_other_payments
--

CREATE TABLE ci_payslip_other_payments (
    payslip_other_payment_id SERIAL PRIMARY KEY,
    payslip_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    is_taxable INTEGER NOT NULL,
    is_fixed INTEGER NOT NULL,
    pay_title varchar(200) NOT NULL,
    pay_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    salary_month varchar(200) NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_payslip_other_payments
--

TRUNCATE TABLE ci_payslip_other_payments;
-- --------------------------------------------------------

--
-- Table structure for table ci_payslip_statutory_deductions
--

CREATE TABLE ci_payslip_statutory_deductions (
    payslip_deduction_id SERIAL PRIMARY KEY,
    payslip_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    is_fixed INTEGER NOT NULL,
    pay_title varchar(200) NOT NULL,
    pay_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    salary_month varchar(200) NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_payslip_statutory_deductions
--

TRUNCATE TABLE ci_payslip_statutory_deductions;
-- --------------------------------------------------------

--
-- Table structure for table ci_performance_appraisal
--

CREATE TABLE ci_performance_appraisal (
    performance_appraisal_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    title varchar(200) DEFAULT NULL,
    appraisal_year_month varchar(255) NOT NULL,
    remarks TEXT NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_performance_appraisal
--

TRUNCATE TABLE ci_performance_appraisal;
-- --------------------------------------------------------

--
-- Table structure for table ci_performance_appraisal_options
--

CREATE TABLE ci_performance_appraisal_options (
    performance_appraisal_options_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    appraisal_id INTEGER NOT NULL,
    appraisal_type varchar(200) NOT NULL,
    appraisal_option_id INTEGER NOT NULL,
    appraisal_option_value INTEGER NOT NULL
);


--
-- Truncate table before insert ci_performance_appraisal_options
--

TRUNCATE TABLE ci_performance_appraisal_options;
-- --------------------------------------------------------

--
-- Table structure for table ci_performance_indicator
--

CREATE TABLE ci_performance_indicator (
    performance_indicator_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    title varchar(255) DEFAULT NULL,
    designation_id INTEGER NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_performance_indicator
--

TRUNCATE TABLE ci_performance_indicator;
-- --------------------------------------------------------

--
-- Table structure for table ci_performance_indicator_options
--

CREATE TABLE ci_performance_indicator_options (
    performance_indicator_options_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    indicator_id INTEGER NOT NULL,
    indicator_type varchar(200) NOT NULL,
    indicator_option_id INTEGER NOT NULL,
    indicator_option_value INTEGER NOT NULL
);


--
-- Truncate table before insert ci_performance_indicator_options
--

TRUNCATE TABLE ci_performance_indicator_options;
-- --------------------------------------------------------

--
-- Table structure for table ci_policies
--

CREATE TABLE ci_policies (
    policy_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    title varchar(255) NOT NULL,
    description TEXT NOT NULL,
    attachment varchar(255) DEFAULT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_policies
--

TRUNCATE TABLE ci_policies;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects
--

CREATE TABLE ci_projects (
    project_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    title varchar(255) NOT NULL,
    start_date varchar(255) NOT NULL,
    end_date varchar(255) NOT NULL,
    assigned_to TEXT DEFAULT NULL,
    associated_goals text DEFAULT NULL,
    priority varchar(255) NOT NULL,
    project_no varchar(255) DEFAULT NULL,
    budget_hours varchar(255) DEFAULT NULL,
    summary TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    project_progress varchar(255) NOT NULL,
    project_note TEXT DEFAULT NULL,
    status SMALLINT NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_projects
--

TRUNCATE TABLE ci_projects;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects_bugs
--

CREATE TABLE ci_projects_bugs (
    project_bug_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    bug_note text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_projects_bugs
--

TRUNCATE TABLE ci_projects_bugs;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects_discussion
--

CREATE TABLE ci_projects_discussion (
    project_discussion_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    discussion_text text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_projects_discussion
--

TRUNCATE TABLE ci_projects_discussion;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects_files
--

CREATE TABLE ci_projects_files (
    project_file_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    file_title varchar(255) NOT NULL,
    attachment_file TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_projects_files
--

TRUNCATE TABLE ci_projects_files;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects_notes
--

CREATE TABLE ci_projects_notes (
    project_note_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    project_note text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_projects_notes
--

TRUNCATE TABLE ci_projects_notes;
-- --------------------------------------------------------

--
-- Table structure for table ci_projects_timelogs
--

CREATE TABLE ci_projects_timelogs (
    timelogs_id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    start_time varchar(255) NOT NULL,
    end_time varchar(255) NOT NULL,
    start_date varchar(255) NOT NULL,
    end_date varchar(255) NOT NULL,
    total_hours varchar(255) NOT NULL,
    timelogs_memo text NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_projects_timelogs
--

TRUNCATE TABLE ci_projects_timelogs;
-- --------------------------------------------------------

--
-- Table structure for table ci_recent_activity
--

CREATE TABLE ci_recent_activity (
    activity_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    module_type varchar(200) NOT NULL,
    is_read INTEGER NOT NULL DEFAULT 0,
    added_by INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_recent_activity
--

TRUNCATE TABLE ci_recent_activity;
-- --------------------------------------------------------

--
-- Table structure for table ci_rec_candidates
--

CREATE TABLE ci_rec_candidates (
    candidate_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    designation_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    job_resume TEXT NOT NULL,
    application_status INTEGER NOT NULL DEFAULT 0,
    application_remarks TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_rec_candidates
--

TRUNCATE TABLE ci_rec_candidates;
-- --------------------------------------------------------

--
-- Table structure for table ci_rec_interviews
--

CREATE TABLE ci_rec_interviews (
    job_interview_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    designation_id INTEGER NOT NULL,
    staff_id varchar(11) NOT NULL,
    interview_place varchar(255) NOT NULL,
    interview_date varchar(255) NOT NULL,
    interview_time varchar(255) NOT NULL,
    interviewer_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    interview_remarks text DEFAULT NULL,
    status INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_rec_interviews
--

TRUNCATE TABLE ci_rec_interviews;
-- --------------------------------------------------------

--
-- Table structure for table ci_rec_jobs
--

CREATE TABLE ci_rec_jobs (
    job_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    job_title varchar(255) NOT NULL,
    designation_id INTEGER NOT NULL,
    job_type INTEGER NOT NULL,
    job_vacancy INTEGER NOT NULL,
    gender varchar(100) NOT NULL,
    minimum_experience varchar(255) NOT NULL,
    date_of_closing varchar(200) NOT NULL,
    short_description TEXT NOT NULL,
    long_description TEXT NOT NULL,
    status INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_rec_jobs
--

TRUNCATE TABLE ci_rec_jobs;
-- --------------------------------------------------------

--
-- Table structure for table ci_resignations
--

CREATE TABLE ci_resignations (
    resignation_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    notice_date varchar(255) NOT NULL,
    resignation_date varchar(255) NOT NULL,
    reason TEXT NOT NULL,
    added_by INTEGER NOT NULL,
    status INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_resignations
--

TRUNCATE TABLE ci_resignations;
-- --------------------------------------------------------

--
-- Table structure for table ci_sms_template
--

CREATE TABLE ci_sms_template (
    template_id INTEGER NOT NULL,
    subject varchar(255) DEFAULT NULL,
    message text DEFAULT NULL,
    created_at varchar(255) DEFAULT NULL
);


--
-- Truncate table before insert ci_sms_template
--

TRUNCATE TABLE ci_sms_template;
--
-- Dumping data for table ci_sms_template
--

INSERT INTO ci_sms_template (template_id, subject, message, created_at) VALUES
(1, 'New Project Assigned', 'Hello {firstname}, you have been assigned a new project {project_name}', '2021-07-01'),
(2, 'New Task Assigned', 'Hello {firstname}, you have been assigned a new task {task_name}', '2021-07-01'),
(3, 'New Award', 'Hello {firstname}, you have been awarded with {award_name}', '2021-07-01'),
(4, 'Leave Approved', 'Hello {firstname}, you leave has been approved {leave_name}', '2021-07-01'),
(5, 'Leave Rejected', 'Hello {firstname}, you leave has been rejected {leave_name}', '2021-07-01'),
(6, 'Payslip Created', 'Hello {firstname}, your salary has been paid. Amount {salary_amount}', '2021-07-01');

-- --------------------------------------------------------

--
-- Table structure for table ci_staff_roles
--

CREATE TABLE ci_staff_roles (
    role_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    role_name varchar(200) NOT NULL,
    role_access varchar(200) NOT NULL,
    role_resources TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_staff_roles
--

TRUNCATE TABLE ci_staff_roles;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_orders
--

CREATE TABLE ci_stock_orders (
    order_id SERIAL PRIMARY KEY,
    order_number varchar(255) NOT NULL,
    company_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    invoice_month varchar(255) DEFAULT NULL,
    invoice_date varchar(255) NOT NULL,
    invoice_due_date varchar(255) NOT NULL,
    sub_total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_type varchar(11) NOT NULL,
    discount_figure NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_tax NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    tax_type varchar(100) DEFAULT NULL,
    total_discount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    grand_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    invoice_note TEXT NOT NULL,
    status SMALLINT NOT NULL,
    payment_method INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_orders
--

TRUNCATE TABLE ci_stock_orders;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_order_items
--

CREATE TABLE ci_stock_order_items (
    order_item_id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    item_id varchar(255) NOT NULL,
    item_qty varchar(255) NOT NULL,
    item_unit_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    item_sub_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_order_items
--

TRUNCATE TABLE ci_stock_order_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_order_quotes
--

CREATE TABLE ci_stock_order_quotes (
    quote_id SERIAL PRIMARY KEY,
    quote_number varchar(255) NOT NULL,
    company_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    quote_month varchar(255) DEFAULT NULL,
    quote_date varchar(255) NOT NULL,
    quote_due_date varchar(255) NOT NULL,
    sub_total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_type varchar(11) NOT NULL,
    discount_figure NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_tax NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    tax_type varchar(100) DEFAULT NULL,
    total_discount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    grand_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    quote_note TEXT NOT NULL,
    status SMALLINT NOT NULL,
    payment_method INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_order_quotes
--

TRUNCATE TABLE ci_stock_order_quotes;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_order_quote_items
--

CREATE TABLE ci_stock_order_quote_items (
    quote_item_id SERIAL PRIMARY KEY,
    quote_id INTEGER NOT NULL,
    item_id varchar(255) NOT NULL,
    item_qty varchar(255) NOT NULL,
    item_unit_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    item_sub_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_order_quote_items
--

TRUNCATE TABLE ci_stock_order_quote_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_products
--

CREATE TABLE ci_stock_products (
    product_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    product_name varchar(255) DEFAULT NULL,
    product_qty INTEGER NOT NULL,
    reorder_stock INTEGER NOT NULL,
    barcode varchar(255) DEFAULT NULL,
    barcode_type varchar(255) DEFAULT NULL,
    warehouse_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    product_sku varchar(255) DEFAULT NULL,
    product_serial_number varchar(255) DEFAULT NULL,
    purchase_price NUMERIC(12,2) NOT NULL,
    retail_price NUMERIC(12,2) NOT NULL,
    expiration_date varchar(255) DEFAULT NULL,
    product_image varchar(255) DEFAULT NULL,
    product_description TEXT DEFAULT NULL,
    product_rating INTEGER NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) DEFAULT NULL,
    status SMALLINT NOT NULL
);


--
-- Truncate table before insert ci_stock_products
--

TRUNCATE TABLE ci_stock_products;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_purchases
--

CREATE TABLE ci_stock_purchases (
    purchase_id SERIAL PRIMARY KEY,
    purchase_number varchar(255) NOT NULL,
    company_id INTEGER NOT NULL,
    supplier_id INTEGER NOT NULL,
    purchase_month varchar(255) DEFAULT NULL,
    purchase_date varchar(255) NOT NULL,
    sub_total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_type varchar(11) NOT NULL,
    discount_figure NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_tax NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    tax_type varchar(100) DEFAULT NULL,
    total_discount NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    grand_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    purchase_note TEXT NOT NULL,
    status SMALLINT NOT NULL,
    payment_method INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_purchases
--

TRUNCATE TABLE ci_stock_purchases;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_purchase_items
--

CREATE TABLE ci_stock_purchase_items (
    purchase_item_id SERIAL PRIMARY KEY,
    purchase_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    item_qty varchar(255) NOT NULL,
    item_unit_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    item_sub_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_purchase_items
--

TRUNCATE TABLE ci_stock_purchase_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_suppliers
--

CREATE TABLE ci_stock_suppliers (
    supplier_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    supplier_name varchar(255) NOT NULL,
    registration_no varchar(255) DEFAULT NULL,
    email varchar(255) DEFAULT NULL,
    contact_number varchar(255) DEFAULT NULL,
    website_url varchar(255) DEFAULT NULL,
    address_1 text DEFAULT NULL,
    address_2 text DEFAULT NULL,
    city varchar(255) DEFAULT NULL,
    state varchar(255) DEFAULT NULL,
    zipcode varchar(255) DEFAULT NULL,
    country INTEGER NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_stock_suppliers
--

TRUNCATE TABLE ci_stock_suppliers;
-- --------------------------------------------------------

--
-- Table structure for table ci_stock_warehouses
--

CREATE TABLE ci_stock_warehouses (
    warehouse_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    warehouse_name varchar(200) DEFAULT NULL,
    contact_number varchar(255) DEFAULT NULL,
    pickup_location SMALLINT NOT NULL,
    address_1 text DEFAULT NULL,
    address_2 text DEFAULT NULL,
    city varchar(255) DEFAULT NULL,
    state varchar(255) DEFAULT NULL,
    zipcode varchar(255) DEFAULT NULL,
    country INTEGER NOT NULL,
    added_by INTEGER NOT NULL,
    status SMALLINT NOT NULL DEFAULT 1,
    created_at varchar(200) DEFAULT NULL
);


--
-- Truncate table before insert ci_stock_warehouses
--

TRUNCATE TABLE ci_stock_warehouses;
-- --------------------------------------------------------

--
-- Table structure for table ci_support_tickets
--

CREATE TABLE ci_support_tickets (
    ticket_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    ticket_code varchar(200) NOT NULL,
    subject varchar(255) NOT NULL,
    employee_id INTEGER NOT NULL,
    ticket_priority varchar(255) NOT NULL,
    department_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    ticket_remarks TEXT DEFAULT NULL,
    ticket_status varchar(200) NOT NULL,
    created_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_support_tickets
--

TRUNCATE TABLE ci_support_tickets;
-- --------------------------------------------------------

--
-- Table structure for table ci_support_ticket_files
--

CREATE TABLE ci_support_ticket_files (
    ticket_file_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    ticket_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    file_title varchar(255) NOT NULL,
    attachment_file TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_support_ticket_files
--

TRUNCATE TABLE ci_support_ticket_files;
-- --------------------------------------------------------

--
-- Table structure for table ci_support_ticket_notes
--

CREATE TABLE ci_support_ticket_notes (
    ticket_note_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    ticket_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    ticket_note text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_support_ticket_notes
--

TRUNCATE TABLE ci_support_ticket_notes;
-- --------------------------------------------------------

--
-- Table structure for table ci_support_ticket_reply
--

CREATE TABLE ci_support_ticket_reply (
    ticket_reply_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    ticket_id INTEGER NOT NULL,
    sent_by INTEGER NOT NULL,
    assign_to INTEGER NOT NULL,
    reply_text text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_support_ticket_reply
--

TRUNCATE TABLE ci_support_ticket_reply;
-- --------------------------------------------------------

--
-- Table structure for table ci_system_documents
--

CREATE TABLE ci_system_documents (
    document_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    document_name varchar(255) NOT NULL,
    document_type varchar(255) NOT NULL,
    document_file varchar(255) NOT NULL,
    created_at varchar(200) DEFAULT NULL
);


--
-- Truncate table before insert ci_system_documents
--

TRUNCATE TABLE ci_system_documents;
-- --------------------------------------------------------

--
-- Table structure for table ci_tasks
--

CREATE TABLE ci_tasks (
    task_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    project_id INTEGER NOT NULL,
    task_name varchar(255) NOT NULL,
    assigned_to varchar(255) DEFAULT NULL,
    associated_goals text DEFAULT NULL,
    start_date varchar(200) NOT NULL,
    end_date varchar(200) NOT NULL,
    task_hour varchar(200) DEFAULT NULL,
    task_progress varchar(200) DEFAULT NULL,
    summary text NOT NULL,
    description TEXT DEFAULT NULL,
    task_status INTEGER NOT NULL,
    task_note TEXT DEFAULT NULL,
    created_by INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_tasks
--

TRUNCATE TABLE ci_tasks;
-- --------------------------------------------------------

--
-- Table structure for table ci_tasks_discussion
--

CREATE TABLE ci_tasks_discussion (
    task_discussion_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    discussion_text text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_tasks_discussion
--

TRUNCATE TABLE ci_tasks_discussion;
-- --------------------------------------------------------

--
-- Table structure for table ci_tasks_files
--

CREATE TABLE ci_tasks_files (
    task_file_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    file_title varchar(255) NOT NULL,
    attachment_file TEXT NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_tasks_files
--

TRUNCATE TABLE ci_tasks_files;
-- --------------------------------------------------------

--
-- Table structure for table ci_tasks_notes
--

CREATE TABLE ci_tasks_notes (
    task_note_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    task_note text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_tasks_notes
--

TRUNCATE TABLE ci_tasks_notes;
-- --------------------------------------------------------

--
-- Table structure for table ci_timesheet
--

CREATE TABLE ci_timesheet (
    time_attendance_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    attendance_date varchar(255) NOT NULL,
    clock_in varchar(255) NOT NULL,
    clock_in_ip_address varchar(255) NOT NULL,
    clock_out varchar(255) NOT NULL,
    clock_out_ip_address varchar(255) NOT NULL,
    clock_in_out varchar(255) NOT NULL,
    clock_in_latitude varchar(150) NOT NULL,
    clock_in_longitude varchar(150) NOT NULL,
    clock_out_latitude varchar(150) NOT NULL,
    clock_out_longitude varchar(150) NOT NULL,
    time_late varchar(255) NOT NULL,
    early_leaving varchar(255) NOT NULL,
    overtime varchar(255) NOT NULL,
    total_work varchar(255) NOT NULL,
    total_rest varchar(255) NOT NULL,
    attendance_status varchar(100) NOT NULL
);


--
-- Truncate table before insert ci_timesheet
--

TRUNCATE TABLE ci_timesheet;
-- --------------------------------------------------------

--
-- Table structure for table ci_timesheet_request
--

CREATE TABLE ci_timesheet_request (
    time_request_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    request_date varchar(255) NOT NULL,
    request_month varchar(255) NOT NULL,
    clock_in varchar(200) NOT NULL,
    clock_out varchar(200) NOT NULL,
    total_hours varchar(255) NOT NULL,
    request_reason TEXT NOT NULL,
    is_approved SMALLINT NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_timesheet_request
--

TRUNCATE TABLE ci_timesheet_request;
-- --------------------------------------------------------

--
-- Table structure for table ci_todo_items
--

CREATE TABLE ci_todo_items (
    todo_item_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    description TEXT DEFAULT NULL,
    is_done INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_todo_items
--

TRUNCATE TABLE ci_todo_items;
-- --------------------------------------------------------

--
-- Table structure for table ci_track_goals
--

CREATE TABLE ci_track_goals (
    tracking_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    tracking_type_id INTEGER NOT NULL,
    start_date varchar(200) NOT NULL,
    end_date varchar(200) NOT NULL,
    subject varchar(255) NOT NULL,
    target_achiement varchar(255) NOT NULL,
    description TEXT DEFAULT NULL,
    goal_work text DEFAULT NULL,
    goal_progress varchar(200) DEFAULT NULL,
    goal_status INTEGER NOT NULL DEFAULT 0,
    goal_rating INTEGER NOT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_track_goals
--

TRUNCATE TABLE ci_track_goals;
-- --------------------------------------------------------

--
-- Table structure for table ci_trainers
--

CREATE TABLE ci_trainers (
    trainer_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    contact_number varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    expertise TEXT NOT NULL,
    address TEXT NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_trainers
--

TRUNCATE TABLE ci_trainers;
-- --------------------------------------------------------

--
-- Table structure for table ci_training
--

CREATE TABLE ci_training (
    training_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id varchar(200) NOT NULL,
    training_type_id INTEGER NOT NULL,
    associated_goals text DEFAULT NULL,
    trainer_id INTEGER NOT NULL,
    start_date varchar(200) NOT NULL,
    finish_date varchar(200) NOT NULL,
    training_cost NUMERIC(12,2) DEFAULT NULL,
    training_status INTEGER DEFAULT NULL,
    description TEXT DEFAULT NULL,
    performance varchar(200) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at varchar(200) NOT NULL
);


--
-- Truncate table before insert ci_training
--

TRUNCATE TABLE ci_training;
-- --------------------------------------------------------

--
-- Table structure for table ci_training_notes
--

CREATE TABLE ci_training_notes (
    training_note_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    training_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    training_note text DEFAULT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_training_notes
--

TRUNCATE TABLE ci_training_notes;
-- --------------------------------------------------------

--
-- Table structure for table ci_transfers
--

CREATE TABLE ci_transfers (
    transfer_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    transfer_date varchar(255) NOT NULL,
    transfer_department INTEGER NOT NULL,
    transfer_designation INTEGER NOT NULL,
    reason TEXT NOT NULL,
    status SMALLINT NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_transfers
--

TRUNCATE TABLE ci_transfers;
-- --------------------------------------------------------

--
-- Table structure for table ci_travels
--

CREATE TABLE ci_travels (
    travel_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    start_date varchar(255) NOT NULL,
    end_date varchar(255) NOT NULL,
    associated_goals text DEFAULT NULL,
    visit_purpose varchar(255) NOT NULL,
    visit_place varchar(255) NOT NULL,
    travel_mode INTEGER DEFAULT NULL,
    arrangement_type INTEGER DEFAULT NULL,
    expected_budget NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    actual_budget NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    description TEXT NOT NULL,
    status SMALLINT NOT NULL,
    added_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_travels
--

TRUNCATE TABLE ci_travels;
-- --------------------------------------------------------

--
-- Table structure for table ci_users_documents
--

CREATE TABLE ci_users_documents (
    document_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    document_name varchar(255) NOT NULL,
    document_type varchar(255) NOT NULL,
    document_file varchar(255) NOT NULL,
    created_at varchar(200) DEFAULT NULL
);


--
-- Truncate table before insert ci_users_documents
--

TRUNCATE TABLE ci_users_documents;
-- --------------------------------------------------------

--
-- Table structure for table ci_visitors
--

CREATE TABLE ci_visitors (
    visitor_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    visit_purpose varchar(255) DEFAULT NULL,
    visitor_name varchar(255) DEFAULT NULL,
    phone varchar(255) DEFAULT NULL,
    email varchar(255) DEFAULT NULL,
    visit_date varchar(255) DEFAULT NULL,
    check_in varchar(255) DEFAULT NULL,
    check_out varchar(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_by INTEGER NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_visitors
--

TRUNCATE TABLE ci_visitors;
-- --------------------------------------------------------

--
-- Table structure for table ci_warnings
--

CREATE TABLE ci_warnings (
    warning_id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL,
    warning_to INTEGER NOT NULL,
    warning_by INTEGER NOT NULL,
    warning_date varchar(255) NOT NULL,
    warning_type_id INTEGER NOT NULL,
    attachment varchar(255) DEFAULT NULL,
    subject varchar(255) NOT NULL,
    description TEXT NOT NULL,
    created_at varchar(255) NOT NULL
);


--
-- Truncate table before insert ci_warnings
--

TRUNCATE TABLE ci_warnings;



-- --------------------------------------------------------
-- Phase 0b: New tables for Rooibok HR
-- --------------------------------------------------------

-- Attendance edit audit trail (Phase 2)
CREATE TABLE ci_attendance_audit (
    audit_id        SERIAL PRIMARY KEY,
    attendance_id   INTEGER NOT NULL,
    company_id      INTEGER NOT NULL,
    changed_by      INTEGER NOT NULL,
    field_changed   VARCHAR(50),
    old_value       TEXT,
    new_value       TEXT,
    changed_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Subscription invoices (Phase 4)
CREATE TABLE ci_subscription_invoices (
    invoice_id          SERIAL PRIMARY KEY,
    invoice_number      VARCHAR(30) UNIQUE NOT NULL,
    company_id          INTEGER NOT NULL,
    membership_id       INTEGER,
    amount              NUMERIC(12,2) NOT NULL,
    currency            VARCHAR(10) DEFAULT 'UGX',
    payment_method      VARCHAR(30),
    transaction_ref     VARCHAR(200),
    pdf_path            VARCHAR(300),
    issued_at           TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    status              VARCHAR(20) DEFAULT 'paid'
);

-- Billing reminders log (Phase 4)
CREATE TABLE ci_billing_reminders_log (
    log_id          SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    reminder_day    SMALLINT NOT NULL,
    sent_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    channel         VARCHAR(10)
);

-- Landing page CMS content (Phase 5)
CREATE TABLE ci_landing_content (
    content_id      SERIAL PRIMARY KEY,
    section         VARCHAR(50) NOT NULL,
    content_key     VARCHAR(100) NOT NULL,
    content_value   TEXT,
    content_json    JSONB,
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE (section, content_key)
);

-- PAYE tax bands (Phase 5)
CREATE TABLE ci_paye_bands (
    band_id         SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    min_income      NUMERIC(12,2) NOT NULL,
    max_income      NUMERIC(12,2),
    rate_percent    NUMERIC(5,2) NOT NULL,
    effective_from  DATE NOT NULL,
    is_active       SMALLINT DEFAULT 1
);

-- Phase 0c: Rename to Rooibok HR System
UPDATE ci_erp_settings
SET
    application_name = 'Rooibok HR System',
    company_name     = 'Rooibok HR System',
    trading_name     = 'Rooibok'
WHERE setting_id = 1;

UPDATE ci_erp_settings
SET
    logo          = 'rooibok-logo-white.png',
    favicon       = 'rooibok-favicon.png',
    frontend_logo = 'rooibok-logo-main.png'
WHERE setting_id = 1;

-- ─────────────────────────────────────────────
-- Phase 1.4: Super Admin Settings — new columns
-- ─────────────────────────────────────────────

-- Stripe
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS stripe_secret_key      VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS stripe_publishable_key  VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS stripe_webhook_secret   VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS stripe_mode             VARCHAR(10) DEFAULT 'test';
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS stripe_active           SMALLINT DEFAULT 0;

-- MTN Mobile Money
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS mtn_subscription_key   VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS mtn_api_user            VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS mtn_api_key             VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS mtn_environment         VARCHAR(20) DEFAULT 'sandbox';
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS mtn_active              SMALLINT DEFAULT 0;

-- Airtel Money
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS airtel_client_id        VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS airtel_client_secret    VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS airtel_environment      VARCHAR(20) DEFAULT 'sandbox';
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS airtel_active           SMALLINT DEFAULT 0;

-- SMS
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS sms_provider            VARCHAR(30) DEFAULT 'africastalking';
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS sms_username            VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS sms_api_key             VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS sms_sender_id           VARCHAR(15) DEFAULT 'RooibokHR';
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS sms_active              SMALLINT DEFAULT 0;

-- JWT / API
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS jwt_secret              VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS jwt_ttl_hours           SMALLINT DEFAULT 24;
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS api_active              SMALLINT DEFAULT 1;
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS api_rate_limit          SMALLINT DEFAULT 60;

-- Geofencing defaults
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS default_geofence_radius INTEGER DEFAULT 300;

-- Billing reminders
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS billing_reminder_active SMALLINT DEFAULT 1;
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS billing_reminder_days   VARCHAR(20) DEFAULT '7,5,3,2,1';

-- NSSF (Phase 5.3)
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS nssf_employee_rate NUMERIC(5,2) DEFAULT 5.00;
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS nssf_employer_rate NUMERIC(5,2) DEFAULT 10.00;
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS nssf_enabled SMALLINT DEFAULT 1;

-- ─────────────────────────────────────────────
-- Phase 1.5: Database Indexes
-- ─────────────────────────────────────────────

-- Columns needed before indexes
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS expiry_date DATE;
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS is_active SMALLINT DEFAULT 1;

CREATE INDEX idx_users_company_type    ON ci_erp_users(company_id, user_type);
CREATE INDEX idx_timesheet_employee    ON ci_timesheet(employee_id, attendance_date);
CREATE INDEX idx_timesheet_company     ON ci_timesheet(company_id, attendance_date);
CREATE INDEX idx_company_membership    ON ci_company_membership(company_id, expiry_date);
CREATE INDEX idx_leave_employee        ON ci_leave_applications(employee_id, status);
CREATE INDEX idx_payslips_company      ON ci_payslips(company_id, salary_month);
CREATE INDEX idx_visitors_company      ON ci_visitors(company_id, created_at);
CREATE INDEX idx_invoices_company      ON ci_invoices(company_id, created_at);
CREATE INDEX idx_finance_transactions  ON ci_finance_transactions(company_id, created_at);

-- ─────────────────────────────────────────────
-- Phase 2: Security and Auth
-- ─────────────────────────────────────────────

-- 2.1: Two-Factor Authentication
ALTER TABLE ci_erp_users ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(32);
ALTER TABLE ci_erp_users ADD COLUMN IF NOT EXISTS totp_enabled SMALLINT DEFAULT 0;

CREATE TABLE ci_totp_backup_codes (
    code_id         SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL,
    code_hash       VARCHAR(255) NOT NULL,
    is_used         SMALLINT DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_totp_backup_user ON ci_totp_backup_codes(user_id, is_used);

-- 2.4: Geofencing columns on timesheet
ALTER TABLE ci_timesheet ADD COLUMN IF NOT EXISTS geofence_flag SMALLINT DEFAULT 0;
ALTER TABLE ci_timesheet ADD COLUMN IF NOT EXISTS clock_in_latitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN IF NOT EXISTS clock_in_longitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN IF NOT EXISTS clock_out_latitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN IF NOT EXISTS clock_out_longitude_dec NUMERIC(10,7);

-- 2.4: Company-level geofence settings
ALTER TABLE ci_erp_company_settings ADD COLUMN IF NOT EXISTS office_latitude NUMERIC(10,7);
ALTER TABLE ci_erp_company_settings ADD COLUMN IF NOT EXISTS office_longitude NUMERIC(10,7);
ALTER TABLE ci_erp_company_settings ADD COLUMN IF NOT EXISTS geofence_radius_m INTEGER DEFAULT 300;

-- ─────────────────────────────────────────────
-- Phase 3: Payment Engine
-- ─────────────────────────────────────────────

-- 3.1: Remove PayPal columns
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_email;
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_sandbox;
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_active;

-- 3.2: Stripe subscription billing + missing core columns
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS expiry_date DATE;
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS is_active SMALLINT DEFAULT 1;
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS billing_mode       VARCHAR(10) DEFAULT 'manual';
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(100);
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS stripe_sub_id      VARCHAR(100);
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS auto_renew         SMALLINT DEFAULT 0;

-- ─────────────────────────────────────────────
-- Phase 4: Subscription Lifecycle
-- ─────────────────────────────────────────────

-- 4.2: In-app notifications
CREATE TABLE ci_notifications (
    notification_id SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL,
    company_id      INTEGER,
    title           VARCHAR(200) NOT NULL,
    body            TEXT,
    link            VARCHAR(300),
    is_read         SMALLINT DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_notifications_user ON ci_notifications(user_id, is_read);

-- 4.3: Show modal flag for urgent renewal
ALTER TABLE ci_company_membership ADD COLUMN IF NOT EXISTS show_modal SMALLINT DEFAULT 0;

-- ─────────────────────────────────────────────
-- Phase 5: Landing Page, Demo, Compliance
-- ─────────────────────────────────────────────

-- 5.2: Demo account flag
ALTER TABLE ci_erp_users ADD COLUMN IF NOT EXISTS is_demo SMALLINT DEFAULT 0;

-- 5.5: Broadcast system
CREATE TABLE ci_broadcasts (
    broadcast_id    SERIAL PRIMARY KEY,
    company_id      INTEGER,
    created_by      INTEGER NOT NULL,
    broadcast_type  VARCHAR(20) NOT NULL,
    subject         TEXT NOT NULL,
    body_html       TEXT,
    body_sms        VARCHAR(320),
    audience_type   VARCHAR(30) NOT NULL,
    audience_ids    JSONB,
    channels        JSONB NOT NULL,
    status          VARCHAR(20) DEFAULT 'draft',
    scheduled_at    TIMESTAMP WITH TIME ZONE,
    sent_at         TIMESTAMP WITH TIME ZONE,
    total_recipients INTEGER DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE ci_broadcast_log (
    log_id          SERIAL PRIMARY KEY,
    broadcast_id    INTEGER NOT NULL REFERENCES ci_broadcasts(broadcast_id),
    recipient_id    INTEGER NOT NULL,
    recipient_type  VARCHAR(20) NOT NULL,
    recipient_email VARCHAR(200),
    recipient_phone VARCHAR(30),
    personalised_subject TEXT,
    personalised_body    TEXT,
    personalised_sms     VARCHAR(320),
    inapp_sent      SMALLINT DEFAULT 0,
    email_sent      SMALLINT DEFAULT 0,
    email_opened    SMALLINT DEFAULT 0,
    sms_sent        SMALLINT DEFAULT 0,
    sms_status      VARCHAR(30),
    error_message   TEXT,
    queued_at       TIMESTAMP WITH TIME ZONE,
    sent_at         TIMESTAMP WITH TIME ZONE
);
CREATE INDEX idx_broadcast_log_broadcast ON ci_broadcast_log(broadcast_id);

CREATE TABLE ci_broadcast_templates (
    template_id     SERIAL PRIMARY KEY,
    company_id      INTEGER,
    template_name   VARCHAR(100) NOT NULL,
    subject         TEXT,
    body_html       TEXT,
    body_sms        VARCHAR(320),
    category        VARCHAR(50),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 5.3: Default Uganda PAYE bands
INSERT INTO ci_paye_bands (company_id, min_income, max_income, rate_percent, effective_from) VALUES
(0, 0,        235000,   0,   '2024-07-01'),
(0, 235001,   335000,   10,  '2024-07-01'),
(0, 335001,   410000,   20,  '2024-07-01'),
(0, 410001,   10000000, 30,  '2024-07-01'),
(0, 10000001, NULL,     40,  '2024-07-01');

-- 5.3: Payroll tax columns on payslips
ALTER TABLE ci_payslips ADD COLUMN IF NOT EXISTS paye_tax NUMERIC(12,2) DEFAULT 0;
ALTER TABLE ci_payslips ADD COLUMN IF NOT EXISTS nssf_employee NUMERIC(12,2) DEFAULT 0;
ALTER TABLE ci_payslips ADD COLUMN IF NOT EXISTS nssf_employer NUMERIC(12,2) DEFAULT 0;

-- Phase 7.3: Expense Claims
CREATE TABLE ci_expenses (
    expense_id      SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    employee_id     INTEGER NOT NULL,
    category_id     INTEGER,
    amount          NUMERIC(12,2) NOT NULL,
    currency        VARCHAR(10) DEFAULT 'UGX',
    description     TEXT,
    expense_date    DATE NOT NULL,
    receipt_path    VARCHAR(300),
    status          VARCHAR(20) DEFAULT 'pending',
    approved_by     INTEGER,
    approved_at     TIMESTAMP WITH TIME ZONE,
    payroll_month   VARCHAR(7),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_expenses_company ON ci_expenses(company_id, status);
CREATE INDEX idx_expenses_employee ON ci_expenses(employee_id, expense_date);

CREATE TABLE ci_expense_categories (
    category_id     SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    category_name   VARCHAR(100) NOT NULL,
    is_active       SMALLINT DEFAULT 1
);

-- ─────────────────────────────────────────────
-- Phase 10: Data Archive Subsystem
-- ─────────────────────────────────────────────

-- Backblaze B2 credentials
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS b2_account_id      VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS b2_application_key VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS b2_bucket_name     VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN IF NOT EXISTS b2_active          SMALLINT DEFAULT 0;

-- Marketing consent on users
ALTER TABLE ci_erp_users ADD COLUMN IF NOT EXISTS marketing_consent SMALLINT DEFAULT 0;
ALTER TABLE ci_erp_users ADD COLUMN IF NOT EXISTS consent_date TIMESTAMP WITH TIME ZONE;

-- =========================================================
-- SEED DATA
-- =========================================================

-- ---------------------------------------------------------
-- 1. Super Admin user (user_id = 1)
-- ---------------------------------------------------------
INSERT INTO ci_erp_users (user_id, user_role_id, user_type, company_id, first_name, last_name, email, username, password, company_name, trading_name, registration_no, government_tax, company_type_id, profile_photo, contact_number, gender, address_1, address_2, city, state, zipcode, country, last_login_date, last_logout_date, last_login_ip, is_logged_in, is_active, created_at, is_demo) VALUES
(1, 0, 'super_user', 0, 'Super', 'Admin', 'admin@hrsale.com', 'superadmin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0700000000', '1', NULL, NULL, 'Kampala', 'Central', '00000', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0);

-- ---------------------------------------------------------
-- 2. Roles in ci_erp_users_role
-- ---------------------------------------------------------
INSERT INTO ci_erp_users_role (role_id, role_name, role_access, role_resources, created_at) VALUES
(1, 'Super Admin', 'all', 'all', '15-03-2026 00:00:00'),
(2, 'Company Admin', 'company', 'all', '15-03-2026 00:00:00'),
(3, 'Staff', 'staff', 'limited', '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 3. Departments (company_id = 2 for demo company)
-- ---------------------------------------------------------
INSERT INTO ci_departments (department_id, department_name, company_id, department_head, added_by, created_at) VALUES
(1, 'Human Resources', 2, 0, 2, '15-03-2026 00:00:00'),
(2, 'Engineering', 2, 0, 2, '15-03-2026 00:00:00'),
(3, 'Finance', 2, 0, 2, '15-03-2026 00:00:00'),
(4, 'Marketing', 2, 0, 2, '15-03-2026 00:00:00'),
(5, 'Operations', 2, 0, 2, '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 4. Designations
-- ---------------------------------------------------------
INSERT INTO ci_designations (designation_id, department_id, company_id, designation_name, description, created_at) VALUES
(1, 1, 2, 'HR Manager', 'Manages human resources department', '15-03-2026 00:00:00'),
(2, 1, 2, 'HR Officer', 'Handles HR operations', '15-03-2026 00:00:00'),
(3, 2, 2, 'Senior Developer', 'Leads software development', '15-03-2026 00:00:00'),
(4, 2, 2, 'Junior Developer', 'Assists in software development', '15-03-2026 00:00:00'),
(5, 3, 2, 'Accountant', 'Manages financial records', '15-03-2026 00:00:00'),
(6, 3, 2, 'Finance Officer', 'Handles financial operations', '15-03-2026 00:00:00'),
(7, 4, 2, 'Marketing Manager', 'Oversees marketing campaigns', '15-03-2026 00:00:00'),
(8, 5, 2, 'Operations Manager', 'Manages daily operations', '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 5. Office shift
-- ---------------------------------------------------------
INSERT INTO ci_office_shifts (office_shift_id, company_id, shift_name, monday_in_time, monday_out_time, tuesday_in_time, tuesday_out_time, wednesday_in_time, wednesday_out_time, thursday_in_time, thursday_out_time, friday_in_time, friday_out_time, saturday_in_time, saturday_out_time, sunday_in_time, sunday_out_time, created_at) VALUES
(1, 2, 'General Shift', '08:00', '17:00', '08:00', '17:00', '08:00', '17:00', '08:00', '17:00', '08:00', '17:00', '08:00', '13:00', '00:00', '00:00', '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 6. Staff employees (user_ids 3-7)
-- ---------------------------------------------------------
INSERT INTO ci_erp_users (user_id, user_role_id, user_type, company_id, first_name, last_name, email, username, password, company_name, trading_name, registration_no, government_tax, company_type_id, profile_photo, contact_number, gender, address_1, address_2, city, state, zipcode, country, last_login_date, last_logout_date, last_login_ip, is_logged_in, is_active, created_at, is_demo) VALUES
(3, 3, 'staff', 2, 'Alice', 'Nakamya', 'alice.nakamya@hrsale.com', 'alice.nakamya', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0701000001', '2', 'Plot 10, Bombo Rd', NULL, 'Kampala', 'Central', '10101', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0),
(4, 3, 'staff', 2, 'Brian', 'Ochieng', 'brian.ochieng@hrsale.com', 'brian.ochieng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0702000002', '1', 'Plot 22, Jinja Rd', NULL, 'Kampala', 'Central', '10102', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0),
(5, 3, 'staff', 2, 'Catherine', 'Auma', 'catherine.auma@hrsale.com', 'catherine.auma', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0703000003', '2', 'Plot 5, Entebbe Rd', NULL, 'Kampala', 'Central', '10103', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0),
(6, 3, 'staff', 2, 'Daniel', 'Mugisha', 'daniel.mugisha@hrsale.com', 'daniel.mugisha', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0704000004', '1', 'Plot 8, Nakasero Hill', NULL, 'Kampala', 'Central', '10104', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0),
(7, 3, 'staff', 2, 'Eva', 'Birungi', 'eva.birungi@hrsale.com', 'eva.birungi', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 'default.png', '0705000005', '2', 'Plot 15, Ntinda Rd', NULL, 'Kampala', 'Central', '10105', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 0);

-- ---------------------------------------------------------
-- 7. Staff details for employees
-- ---------------------------------------------------------
INSERT INTO ci_erp_users_details (staff_details_id, user_id, employee_id, department_id, designation_id, office_shift_id, basic_salary, hourly_rate, salay_type, leave_categories, role_description, date_of_joining, date_of_leaving, date_of_birth, marital_status, religion_id, blood_group, citizenship_id, bio, experience, fb_profile, twitter_profile, gplus_profile, linkedin_profile, account_title, account_number, bank_name, iban, swift_code, bank_branch, contact_full_name, contact_phone_no, contact_email, contact_address, created_at) VALUES
(1, 3, 'EMP-001', 1, 1, 1, 2500000.00, 12019.23, 1, 'all', 'HR Manager', '01-01-2025', NULL, '15-06-1990', 2, NULL, 'O+', NULL, 'Experienced HR professional', 5, NULL, NULL, NULL, NULL, 'Alice Nakamya', '1001001001', 'Stanbic Bank', NULL, 'SBICUGKX', 'Kampala Main', 'John Nakamya', '0771000001', 'john.nakamya@email.com', 'Kampala', '15-03-2026 00:00:00'),
(2, 4, 'EMP-002', 2, 3, 1, 3500000.00, 16826.92, 1, 'all', 'Senior Developer', '15-02-2025', NULL, '20-03-1988', 1, NULL, 'A+', NULL, 'Full-stack developer', 7, NULL, NULL, NULL, NULL, 'Brian Ochieng', '2002002002', 'Stanbic Bank', NULL, 'SBICUGKX', 'Kampala Main', 'Sarah Ochieng', '0772000002', 'sarah.ochieng@email.com', 'Kampala', '15-03-2026 00:00:00'),
(3, 5, 'EMP-003', 3, 5, 1, 2000000.00, 9615.38, 1, 'all', 'Accountant', '01-03-2025', NULL, '10-11-1992', 1, NULL, 'B+', NULL, 'Certified accountant', 3, NULL, NULL, NULL, NULL, 'Catherine Auma', '3003003003', 'DFCU Bank', NULL, 'DFCUUGKA', 'Kampala Branch', 'Peter Auma', '0773000003', 'peter.auma@email.com', 'Kampala', '15-03-2026 00:00:00'),
(4, 6, 'EMP-004', 4, 7, 1, 2800000.00, 13461.54, 1, 'all', 'Marketing Manager', '10-01-2025', NULL, '25-08-1991', 2, NULL, 'AB+', NULL, 'Digital marketing specialist', 4, NULL, NULL, NULL, NULL, 'Daniel Mugisha', '4004004004', 'Centenary Bank', NULL, 'CEABOREB', 'Kampala Main', 'Grace Mugisha', '0774000004', 'grace.mugisha@email.com', 'Kampala', '15-03-2026 00:00:00'),
(5, 7, 'EMP-005', 5, 8, 1, 3000000.00, 14423.08, 1, 'all', 'Operations Manager', '20-01-2025', NULL, '05-04-1989', 1, NULL, 'O-', NULL, 'Operations and logistics expert', 6, NULL, NULL, NULL, NULL, 'Eva Birungi', '5005005005', 'Absa Bank', NULL, 'BABOREBB', 'Kampala Branch', 'Moses Birungi', '0775000005', 'moses.birungi@email.com', 'Kampala', '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 8. Uganda public holidays
-- ---------------------------------------------------------
INSERT INTO ci_holidays (holiday_id, company_id, event_name, description, start_date, end_date, is_publish, created_at) VALUES
(1, 2, 'New Year''s Day', 'New Year''s Day celebration', '01-01-2026', '01-01-2026', 1, '15-03-2026 00:00:00'),
(2, 2, 'NRM Liberation Day', 'National Resistance Movement Liberation Day', '26-01-2026', '26-01-2026', 1, '15-03-2026 00:00:00'),
(3, 2, 'International Women''s Day', 'International Women''s Day', '08-03-2026', '08-03-2026', 1, '15-03-2026 00:00:00'),
(4, 2, 'Labour Day', 'International Workers'' Day', '01-05-2026', '01-05-2026', 1, '15-03-2026 00:00:00'),
(5, 2, 'Uganda Martyrs'' Day', 'Commemoration of the Uganda Martyrs', '03-06-2026', '03-06-2026', 1, '15-03-2026 00:00:00'),
(6, 2, 'National Heroes'' Day', 'Honouring Uganda''s national heroes', '09-06-2026', '09-06-2026', 1, '15-03-2026 00:00:00'),
(7, 2, 'Independence Day', 'Uganda''s Independence Day', '09-10-2026', '09-10-2026', 1, '15-03-2026 00:00:00'),
(8, 2, 'Christmas Day', 'Christmas Day celebration', '25-12-2026', '25-12-2026', 1, '15-03-2026 00:00:00'),
(9, 2, 'Boxing Day', 'Boxing Day', '26-12-2026', '26-12-2026', 1, '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 9. Demo company user (user_id = 8, is_demo = 1)
-- ---------------------------------------------------------
INSERT INTO ci_erp_users (user_id, user_role_id, user_type, company_id, first_name, last_name, email, username, password, company_name, trading_name, registration_no, government_tax, company_type_id, profile_photo, contact_number, gender, address_1, address_2, city, state, zipcode, country, last_login_date, last_logout_date, last_login_ip, is_logged_in, is_active, created_at, is_demo) VALUES
(8, 2, 'company', 0, 'Demo', 'Company', 'demo@hrsale.com', 'demo', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Corp', 'TRD-DEMO', 'RG-DEMO', 'TX-DEMO', 3, 'default.png', '0700000008', '1', 'Plot 1, Demo Street', NULL, 'Kampala', 'Central', '00000', 229, NULL, NULL, NULL, 0, 1, '15-03-2026 00:00:00', 1);

-- ---------------------------------------------------------
-- 10. Leave type constants (5 types for company_id = 2)
-- ---------------------------------------------------------
INSERT INTO ci_erp_constants (constants_id, company_id, type, category_name, field_one, field_two, created_at) VALUES
(200, 2, 'leave_type', 'Annual', '21', '1', '15-03-2026 00:00:00'),
(201, 2, 'leave_type', 'Sick', '10', '1', '15-03-2026 00:00:00'),
(202, 2, 'leave_type', 'Maternity', '60', '1', '15-03-2026 00:00:00'),
(203, 2, 'leave_type', 'Paternity', '7', '1', '15-03-2026 00:00:00'),
(204, 2, 'leave_type', 'Compassionate', '5', '1', '15-03-2026 00:00:00');

-- ---------------------------------------------------------
-- 11. Expense categories (5 categories for company_id = 2)
-- ---------------------------------------------------------
INSERT INTO ci_expense_categories (category_id, company_id, category_name, is_active) VALUES
(1, 2, 'Travel', 1),
(2, 2, 'Office Supplies', 1),
(3, 2, 'Meals & Entertainment', 1),
(4, 2, 'Communication', 1),
(5, 2, 'Training & Development', 1);

-- ---------------------------------------------------------
-- 12. Reset sequences to avoid primary key conflicts
-- ---------------------------------------------------------
SELECT setval('ci_erp_users_user_id_seq', (SELECT COALESCE(MAX(user_id), 1) FROM ci_erp_users));
SELECT setval('ci_erp_users_role_role_id_seq', (SELECT COALESCE(MAX(role_id), 1) FROM ci_erp_users_role));
SELECT setval('ci_departments_department_id_seq', (SELECT COALESCE(MAX(department_id), 1) FROM ci_departments));
SELECT setval('ci_designations_designation_id_seq', (SELECT COALESCE(MAX(designation_id), 1) FROM ci_designations));
SELECT setval('ci_office_shifts_office_shift_id_seq', (SELECT COALESCE(MAX(office_shift_id), 1) FROM ci_office_shifts));
SELECT setval('ci_holidays_holiday_id_seq', (SELECT COALESCE(MAX(holiday_id), 1) FROM ci_holidays));
SELECT setval('ci_erp_users_details_staff_details_id_seq', (SELECT COALESCE(MAX(staff_details_id), 1) FROM ci_erp_users_details));
SELECT setval('ci_erp_constants_constants_id_seq', (SELECT COALESCE(MAX(constants_id), 1) FROM ci_erp_constants));
SELECT setval('ci_expense_categories_category_id_seq', (SELECT COALESCE(MAX(category_id), 1) FROM ci_expense_categories));
