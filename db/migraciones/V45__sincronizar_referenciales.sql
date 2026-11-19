-- Sincroniza los catálogos referenciales con la base de referencia al 2026-11-19 19:43.
-- Generado con db/generar_migracion_referenciales.php; no editar a mano.
-- Solo modifica filas distintas o faltantes y elimina las que ya no existen,
-- por lo que en la base de referencia no produce cambios.

SET @fk_checks_previo = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- paises: 249 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_paises`;
CREATE TEMPORARY TABLE `tmp_ref_paises` LIKE `paises`;
INSERT INTO `tmp_ref_paises` (`id_pais`, `codigo_iso2`, `codigo_iso3`, `nombre`, `prefijo_telefonico`, `activo`) VALUES
    ('1', 'AR', 'ARG', 'Argentina', '+54', '1'),
    ('2', 'BO', 'BOL', 'Bolivia', '+591', '1'),
    ('3', 'BR', 'BRA', 'Brasil', '+55', '1'),
    ('4', 'CL', 'CHL', 'Chile', '+56', '1'),
    ('5', 'CO', 'COL', 'Colombia', '+57', '1'),
    ('6', 'EC', 'ECU', 'Ecuador', '+593', '1'),
    ('7', 'ES', 'ESP', 'España', '+34', '1'),
    ('8', 'MX', 'MEX', 'México', '+52', '1'),
    ('9', 'PY', 'PRY', 'Paraguay', '+595', '1'),
    ('10', 'PE', 'PER', 'Perú', '+51', '1'),
    ('11', 'US', 'USA', 'Estados Unidos', '+1', '1'),
    ('12', 'UY', 'URY', 'Uruguay', '+598', '1'),
    ('13', 'AF', 'AFG', 'Afganistán', '+93', '1'),
    ('14', 'AX', 'ALA', 'Alandia', '+35818', '1'),
    ('15', 'AL', 'ALB', 'Albania', '+355', '1'),
    ('16', 'DE', 'DEU', 'Alemania', '+49', '1'),
    ('17', 'AD', 'AND', 'Andorra', '+376', '1'),
    ('18', 'AO', 'AGO', 'Angola', '+244', '1'),
    ('19', 'AI', 'AIA', 'Anguilla', '+1264', '1'),
    ('20', 'AQ', 'ATA', 'Antártida', '+672', '1'),
    ('21', 'AG', 'ATG', 'Antigua y Barbuda', '+1268', '1'),
    ('22', 'SA', 'SAU', 'Arabia Saudí', '+966', '1'),
    ('23', 'DZ', 'DZA', 'Argelia', '+213', '1'),
    ('24', 'AM', 'ARM', 'Armenia', '+374', '1'),
    ('25', 'AW', 'ABW', 'Aruba', '+297', '1'),
    ('26', 'AU', 'AUS', 'Australia', '+61', '1'),
    ('27', 'AT', 'AUT', 'Austria', '+43', '1'),
    ('28', 'AZ', 'AZE', 'Azerbaiyán', '+994', '1'),
    ('29', 'BS', 'BHS', 'Bahamas', '+1242', '1'),
    ('30', 'BH', 'BHR', 'Bahrein', '+973', '1'),
    ('31', 'BD', 'BGD', 'Bangladesh', '+880', '1'),
    ('32', 'BB', 'BRB', 'Barbados', '+1246', '1'),
    ('33', 'BE', 'BEL', 'Bélgica', '+32', '1'),
    ('34', 'BZ', 'BLZ', 'Belice', '+501', '1'),
    ('35', 'BJ', 'BEN', 'Benín', '+229', '1'),
    ('36', 'BM', 'BMU', 'Bermudas', '+1441', '1'),
    ('37', 'BY', 'BLR', 'Bielorrusia', '+375', '1'),
    ('38', 'BA', 'BIH', 'Bosnia y Herzegovina', '+387', '1'),
    ('39', 'BW', 'BWA', 'Botswana', '+267', '1'),
    ('40', 'BN', 'BRN', 'Brunei', '+673', '1'),
    ('41', 'BG', 'BGR', 'Bulgaria', '+359', '1'),
    ('42', 'BF', 'BFA', 'Burkina Faso', '+226', '1'),
    ('43', 'BI', 'BDI', 'Burundi', '+257', '1'),
    ('44', 'BT', 'BTN', 'Bután', '+975', '1'),
    ('45', 'CV', 'CPV', 'Cabo Verde', '+238', '1'),
    ('46', 'KH', 'KHM', 'Camboya', '+855', '1'),
    ('47', 'CM', 'CMR', 'Camerún', '+237', '1'),
    ('48', 'CA', 'CAN', 'Canadá', '+1', '1'),
    ('49', 'BQ', 'BES', 'Caribe Neerlandés', '+599', '1'),
    ('50', 'QA', 'QAT', 'Catar', '+974', '1'),
    ('51', 'TD', 'TCD', 'Chad', '+235', '1'),
    ('52', 'CZ', 'CZE', 'Chequia', '+420', '1'),
    ('53', 'CN', 'CHN', 'China', '+86', '1'),
    ('54', 'CY', 'CYP', 'Chipre', '+357', '1'),
    ('55', 'VA', 'VAT', 'Ciudad del Vaticano', '+3', '1'),
    ('56', 'KM', 'COM', 'Comoras', '+269', '1'),
    ('57', 'CG', 'COG', 'Congo', '+242', '1'),
    ('58', 'CD', 'COD', 'Congo (Rep. Dem.)', '+243', '1'),
    ('59', 'KP', 'PRK', 'Corea del Norte', '+850', '1'),
    ('60', 'KR', 'KOR', 'Corea del Sur', '+82', '1'),
    ('61', 'CI', 'CIV', 'Costa de Marfil', '+225', '1'),
    ('62', 'CR', 'CRI', 'Costa Rica', '+506', '1'),
    ('63', 'HR', 'HRV', 'Croacia', '+385', '1'),
    ('64', 'CU', 'CUB', 'Cuba', '+53', '1'),
    ('65', 'CW', 'CUW', 'Curazao', '+599', '1'),
    ('66', 'DK', 'DNK', 'Dinamarca', '+45', '1'),
    ('67', 'DJ', 'DJI', 'Djibouti', '+253', '1'),
    ('68', 'DM', 'DMA', 'Dominica', '+1767', '1'),
    ('69', 'EG', 'EGY', 'Egipto', '+20', '1'),
    ('70', 'SV', 'SLV', 'El Salvador', '+503', '1'),
    ('71', 'AE', 'ARE', 'Emiratos Árabes Unidos', '+971', '1'),
    ('72', 'ER', 'ERI', 'Eritrea', '+291', '1'),
    ('73', 'SK', 'SVK', 'Eslovaquia', '+421', '1'),
    ('74', 'SI', 'SVN', 'Eslovenia', '+386', '1'),
    ('75', 'EE', 'EST', 'Estonia', '+372', '1'),
    ('76', 'SZ', 'SWZ', 'Esuatini', '+268', '1'),
    ('77', 'ET', 'ETH', 'Etiopía', '+251', '1'),
    ('78', 'PH', 'PHL', 'Filipinas', '+63', '1'),
    ('79', 'FI', 'FIN', 'Finlandia', '+358', '1'),
    ('80', 'FJ', 'FJI', 'Fiyi', '+679', '1'),
    ('81', 'FR', 'FRA', 'Francia', '+33', '1'),
    ('82', 'GA', 'GAB', 'Gabón', '+241', '1'),
    ('83', 'GM', 'GMB', 'Gambia', '+220', '1'),
    ('84', 'GE', 'GEO', 'Georgia', '+995', '1'),
    ('85', 'GH', 'GHA', 'Ghana', '+233', '1'),
    ('86', 'GI', 'GIB', 'Gibraltar', '+350', '1'),
    ('87', 'GR', 'GRC', 'Grecia', '+30', '1'),
    ('88', 'GD', 'GRD', 'Grenada', '+1473', '1'),
    ('89', 'GL', 'GRL', 'Groenlandia', '+299', '1'),
    ('90', 'GP', 'GLP', 'Guadalupe', '+590', '1'),
    ('91', 'GU', 'GUM', 'Guam', '+1671', '1'),
    ('92', 'GT', 'GTM', 'Guatemala', '+502', '1'),
    ('93', 'GF', 'GUF', 'Guayana Francesa', '+594', '1'),
    ('94', 'GG', 'GGY', 'Guernsey', '+44', '1'),
    ('95', 'GN', 'GIN', 'Guinea', '+224', '1'),
    ('96', 'GQ', 'GNQ', 'Guinea Ecuatorial', '+240', '1'),
    ('97', 'GW', 'GNB', 'Guinea-Bisáu', '+245', '1'),
    ('98', 'GY', 'GUY', 'Guyana', '+592', '1'),
    ('99', 'HT', 'HTI', 'Haití', '+509', '1'),
    ('100', 'HN', 'HND', 'Honduras', '+504', '1'),
    ('101', 'HK', 'HKG', 'Hong Kong', '+852', '1'),
    ('102', 'HU', 'HUN', 'Hungría', '+36', '1'),
    ('103', 'IN', 'IND', 'India', '+91', '1'),
    ('104', 'ID', 'IDN', 'Indonesia', '+62', '1'),
    ('105', 'IQ', 'IRQ', 'Irak', '+964', '1'),
    ('106', 'IR', 'IRN', 'Iran', '+98', '1'),
    ('107', 'IE', 'IRL', 'Irlanda', '+353', '1'),
    ('108', 'BV', 'BVT', 'Isla Bouvet', '+47', '1'),
    ('109', 'IM', 'IMN', 'Isla de Man', '+44', '1'),
    ('110', 'CX', 'CXR', 'Isla de Navidad', '+61', '1'),
    ('111', 'NF', 'NFK', 'Isla de Norfolk', '+672', '1'),
    ('112', 'IS', 'ISL', 'Islandia', '+354', '1'),
    ('113', 'KY', 'CYM', 'Islas Caimán', '+1345', '1'),
    ('114', 'CC', 'CCK', 'Islas Cocos o Islas Keeling', '+61', '1'),
    ('115', 'CK', 'COK', 'Islas Cook', '+682', '1'),
    ('116', 'FO', 'FRO', 'Islas Faroe', '+298', '1'),
    ('117', 'GS', 'SGS', 'Islas Georgias del Sur y Sandwich del Sur', '+500', '1'),
    ('118', 'HM', 'HMD', 'Islas Heard y McDonald', '+672', '1'),
    ('119', 'FK', 'FLK', 'Islas Malvinas', '+500', '1'),
    ('120', 'MP', 'MNP', 'Islas Marianas del Norte', '+1670', '1'),
    ('121', 'MH', 'MHL', 'Islas Marshall', '+692', '1'),
    ('122', 'PN', 'PCN', 'Islas Pitcairn', '+64', '1'),
    ('123', 'SB', 'SLB', 'Islas Salomón', '+677', '1'),
    ('124', 'SJ', 'SJM', 'Islas Svalbard y Jan Mayen', '+4779', '1'),
    ('125', 'TK', 'TKL', 'Islas Tokelau', '+690', '1'),
    ('126', 'TC', 'TCA', 'Islas Turks y Caicos', '+1649', '1'),
    ('127', 'UM', 'UMI', 'Islas Ultramarinas Menores de Estados Unidos', '+268', '1'),
    ('128', 'VI', 'VIR', 'Islas Vírgenes de los Estados Unidos', '+1340', '1'),
    ('129', 'VG', 'VGB', 'Islas Vírgenes del Reino Unido', '+1284', '1'),
    ('130', 'IL', 'ISR', 'Israel', '+972', '1'),
    ('131', 'IT', 'ITA', 'Italia', '+39', '1'),
    ('132', 'JM', 'JAM', 'Jamaica', '+1876', '1'),
    ('133', 'JP', 'JPN', 'Japón', '+81', '1'),
    ('134', 'JE', 'JEY', 'Jersey', '+44', '1'),
    ('135', 'JO', 'JOR', 'Jordania', '+962', '1'),
    ('136', 'KZ', 'KAZ', 'Kazajistán', '+7', '1'),
    ('137', 'KE', 'KEN', 'Kenia', '+254', '1'),
    ('138', 'KG', 'KGZ', 'Kirguizistán', '+996', '1'),
    ('139', 'KI', 'KIR', 'Kiribati', '+686', '1'),
    ('140', 'KW', 'KWT', 'Kuwait', '+965', '1'),
    ('141', 'LA', 'LAO', 'Laos', '+856', '1'),
    ('142', 'LS', 'LSO', 'Lesotho', '+266', '1'),
    ('143', 'LV', 'LVA', 'Letonia', '+371', '1'),
    ('144', 'LB', 'LBN', 'Líbano', '+961', '1'),
    ('145', 'LR', 'LBR', 'Liberia', '+231', '1'),
    ('146', 'LY', 'LBY', 'Libia', '+218', '1'),
    ('147', 'LI', 'LIE', 'Liechtenstein', '+423', '1'),
    ('148', 'LT', 'LTU', 'Lituania', '+370', '1'),
    ('149', 'LU', 'LUX', 'Luxemburgo', '+352', '1'),
    ('150', 'MO', 'MAC', 'Macao', '+853', '1'),
    ('151', 'MK', 'MKD', 'Macedonia del Norte', '+389', '1'),
    ('152', 'MG', 'MDG', 'Madagascar', '+261', '1'),
    ('153', 'MY', 'MYS', 'Malasia', '+60', '1'),
    ('154', 'MW', 'MWI', 'Malawi', '+265', '1'),
    ('155', 'MV', 'MDV', 'Maldivas', '+960', '1'),
    ('156', 'ML', 'MLI', 'Mali', '+223', '1'),
    ('157', 'MT', 'MLT', 'Malta', '+356', '1'),
    ('158', 'MA', 'MAR', 'Marruecos', '+212', '1'),
    ('159', 'MQ', 'MTQ', 'Martinica', '+596', '1'),
    ('160', 'MU', 'MUS', 'Mauricio', '+230', '1'),
    ('161', 'MR', 'MRT', 'Mauritania', '+222', '1'),
    ('162', 'YT', 'MYT', 'Mayotte', '+262', '1'),
    ('163', 'FM', 'FSM', 'Micronesia', '+691', '1'),
    ('164', 'MD', 'MDA', 'Moldavia', '+373', '1'),
    ('165', 'MC', 'MCO', 'Mónaco', '+377', '1'),
    ('166', 'MN', 'MNG', 'Mongolia', '+976', '1'),
    ('167', 'ME', 'MNE', 'Montenegro', '+382', '1'),
    ('168', 'MS', 'MSR', 'Montserrat', '+1664', '1'),
    ('169', 'MZ', 'MOZ', 'Mozambique', '+258', '1'),
    ('170', 'MM', 'MMR', 'Myanmar', '+95', '1'),
    ('171', 'NA', 'NAM', 'Namibia', '+264', '1'),
    ('172', 'NR', 'NRU', 'Nauru', '+674', '1'),
    ('173', 'NP', 'NPL', 'Nepal', '+977', '1'),
    ('174', 'NI', 'NIC', 'Nicaragua', '+505', '1'),
    ('175', 'NE', 'NER', 'Níger', '+227', '1'),
    ('176', 'NG', 'NGA', 'Nigeria', '+234', '1'),
    ('177', 'NU', 'NIU', 'Niue', '+683', '1'),
    ('178', 'NO', 'NOR', 'Noruega', '+47', '1'),
    ('179', 'NC', 'NCL', 'Nueva Caledonia', '+687', '1'),
    ('180', 'NZ', 'NZL', 'Nueva Zelanda', '+64', '1'),
    ('181', 'OM', 'OMN', 'Omán', '+968', '1'),
    ('182', 'NL', 'NLD', 'Países Bajos', '+31', '1'),
    ('183', 'PK', 'PAK', 'Pakistán', '+92', '1'),
    ('184', 'PW', 'PLW', 'Palau', '+680', '1'),
    ('185', 'PS', 'PSE', 'Palestina', '+970', '1'),
    ('186', 'PA', 'PAN', 'Panamá', '+507', '1'),
    ('187', 'PG', 'PNG', 'Papúa Nueva Guinea', '+675', '1'),
    ('188', 'PF', 'PYF', 'Polinesia Francesa', '+689', '1'),
    ('189', 'PL', 'POL', 'Polonia', '+48', '1'),
    ('190', 'PT', 'PRT', 'Portugal', '+351', '1'),
    ('191', 'PR', 'PRI', 'Puerto Rico', '+1787', '1'),
    ('192', 'GB', 'GBR', 'Reino Unido', '+44', '1'),
    ('193', 'CF', 'CAF', 'República Centroafricana', '+236', '1'),
    ('194', 'DO', 'DOM', 'República Dominicana', '+1809', '1'),
    ('195', 'RE', 'REU', 'Reunión', '+262', '1'),
    ('196', 'RW', 'RWA', 'Ruanda', '+250', '1'),
    ('197', 'RO', 'ROU', 'Rumania', '+40', '1'),
    ('198', 'RU', 'RUS', 'Rusia', '+7', '1'),
    ('199', 'EH', 'ESH', 'Sahara Occidental', '+2', '1'),
    ('200', 'MF', 'MAF', 'Saint Martin', '+590', '1');
INSERT INTO `tmp_ref_paises` (`id_pais`, `codigo_iso2`, `codigo_iso3`, `nombre`, `prefijo_telefonico`, `activo`) VALUES
    ('201', 'WS', 'WSM', 'Samoa', '+685', '1'),
    ('202', 'AS', 'ASM', 'Samoa Americana', '+1684', '1'),
    ('203', 'BL', 'BLM', 'San Bartolomé', '+590', '1'),
    ('204', 'KN', 'KNA', 'San Cristóbal y Nieves', '+1869', '1'),
    ('205', 'SM', 'SMR', 'San Marino', '+378', '1'),
    ('206', 'PM', 'SPM', 'San Pedro y Miquelón', '+508', '1'),
    ('207', 'VC', 'VCT', 'San Vicente y Granadinas', '+1784', '1'),
    ('208', 'SH', 'SHN', 'Santa Elena, Ascensión y Tristán de Acuña', '+2', '1'),
    ('209', 'LC', 'LCA', 'Santa Lucía', '+1758', '1'),
    ('210', 'ST', 'STP', 'Santo Tomé y Príncipe', '+239', '1'),
    ('211', 'SN', 'SEN', 'Senegal', '+221', '1'),
    ('212', 'RS', 'SRB', 'Serbia', '+381', '1'),
    ('213', 'SC', 'SYC', 'Seychelles', '+248', '1'),
    ('214', 'SL', 'SLE', 'Sierra Leone', '+232', '1'),
    ('215', 'SG', 'SGP', 'Singapur', '+65', '1'),
    ('216', 'SX', 'SXM', 'Sint Maarten', '+1721', '1'),
    ('217', 'SY', 'SYR', 'Siria', '+963', '1'),
    ('218', 'SO', 'SOM', 'Somalia', '+252', '1'),
    ('219', 'LK', 'LKA', 'Sri Lanka', '+94', '1'),
    ('220', 'ZA', 'ZAF', 'Sudáfrica', '+27', '1'),
    ('221', 'SD', 'SDN', 'Sudán', '+249', '1'),
    ('222', 'SS', 'SSD', 'Sudán del Sur', '+211', '1'),
    ('223', 'SE', 'SWE', 'Suecia', '+46', '1'),
    ('224', 'CH', 'CHE', 'Suiza', '+41', '1'),
    ('225', 'SR', 'SUR', 'Surinam', '+597', '1'),
    ('226', 'TH', 'THA', 'Tailandia', '+66', '1'),
    ('227', 'TW', 'TWN', 'Taiwán', '+886', '1'),
    ('228', 'TZ', 'TZA', 'Tanzania', '+255', '1'),
    ('229', 'TJ', 'TJK', 'Tayikistán', '+992', '1'),
    ('230', 'IO', 'IOT', 'Territorio Británico del Océano Índico', '+246', '1'),
    ('231', 'TF', 'ATF', 'Tierras Australes y Antárticas Francesas', '+262', '1'),
    ('232', 'TL', 'TLS', 'Timor Oriental', '+670', '1'),
    ('233', 'TG', 'TGO', 'Togo', '+228', '1'),
    ('234', 'TO', 'TON', 'Tonga', '+676', '1'),
    ('235', 'TT', 'TTO', 'Trinidad y Tobago', '+1868', '1'),
    ('236', 'TN', 'TUN', 'Túnez', '+216', '1'),
    ('237', 'TM', 'TKM', 'Turkmenistán', '+993', '1'),
    ('238', 'TR', 'TUR', 'Turquía', '+90', '1'),
    ('239', 'TV', 'TUV', 'Tuvalu', '+688', '1'),
    ('240', 'UA', 'UKR', 'Ucrania', '+380', '1'),
    ('241', 'UG', 'UGA', 'Uganda', '+256', '1'),
    ('242', 'UZ', 'UZB', 'Uzbekistán', '+998', '1'),
    ('243', 'VU', 'VUT', 'Vanuatu', '+678', '1'),
    ('244', 'VE', 'VEN', 'Venezuela', '+58', '1'),
    ('245', 'VN', 'VNM', 'Vietnam', '+84', '1'),
    ('246', 'WF', 'WLF', 'Wallis y Futuna', '+681', '1'),
    ('247', 'YE', 'YEM', 'Yemen', '+967', '1'),
    ('248', 'ZM', 'ZMB', 'Zambia', '+260', '1'),
    ('249', 'ZW', 'ZWE', 'Zimbabue', '+263', '1');
DELETE t FROM `paises` t LEFT JOIN `tmp_ref_paises` r ON t.`id_pais` = r.`id_pais`
WHERE r.`id_pais` IS NULL OR NOT (t.`id_pais` <=> r.`id_pais` AND t.`codigo_iso2` <=> r.`codigo_iso2` AND t.`codigo_iso3` <=> r.`codigo_iso3` AND t.`nombre` <=> r.`nombre` AND t.`prefijo_telefonico` <=> r.`prefijo_telefonico` AND t.`activo` <=> r.`activo`);
INSERT INTO `paises` (`id_pais`, `codigo_iso2`, `codigo_iso3`, `nombre`, `prefijo_telefonico`, `activo`)
SELECT r.`id_pais`, r.`codigo_iso2`, r.`codigo_iso3`, r.`nombre`, r.`prefijo_telefonico`, r.`activo` FROM `tmp_ref_paises` r LEFT JOIN `paises` t ON t.`id_pais` = r.`id_pais`
WHERE t.`id_pais` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_paises`;
ALTER TABLE `paises` AUTO_INCREMENT = 250;

-- tipos_estudio: 4 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_tipos_estudio`;
CREATE TEMPORARY TABLE `tmp_ref_tipos_estudio` LIKE `tipos_estudio`;
INSERT INTO `tmp_ref_tipos_estudio` (`id_tipo_estudio`, `codigo`, `nombre`, `activo`) VALUES
    ('1', 'LABORATORIO', 'Laboratorio', '1'),
    ('2', 'IMAGENOLOGIA', 'Imagenología', '1'),
    ('3', 'CONSULTA', 'Consulta clínica', '1'),
    ('4', 'OTRO', 'Otro estudio', '1');
DELETE t FROM `tipos_estudio` t LEFT JOIN `tmp_ref_tipos_estudio` r ON t.`id_tipo_estudio` = r.`id_tipo_estudio`
WHERE r.`id_tipo_estudio` IS NULL OR NOT (t.`id_tipo_estudio` <=> r.`id_tipo_estudio` AND t.`codigo` <=> r.`codigo` AND t.`nombre` <=> r.`nombre` AND t.`activo` <=> r.`activo`);
INSERT INTO `tipos_estudio` (`id_tipo_estudio`, `codigo`, `nombre`, `activo`)
SELECT r.`id_tipo_estudio`, r.`codigo`, r.`nombre`, r.`activo` FROM `tmp_ref_tipos_estudio` r LEFT JOIN `tipos_estudio` t ON t.`id_tipo_estudio` = r.`id_tipo_estudio`
WHERE t.`id_tipo_estudio` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_tipos_estudio`;
ALTER TABLE `tipos_estudio` AUTO_INCREMENT = 5;

-- titulos: 6 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_titulos`;
CREATE TEMPORARY TABLE `tmp_ref_titulos` LIKE `titulos`;
INSERT INTO `tmp_ref_titulos` (`id_titulo`, `titulo`, `abreviatura`, `es_femenino`, `activo`) VALUES
    ('1', 'Doctor', 'Dr.', '0', '1'),
    ('2', 'Doctora', 'Dra.', '1', '1'),
    ('3', 'Licenciado', 'Lic.', '0', '1'),
    ('4', 'Licenciada', 'Lic.', '1', '1'),
    ('5', 'Bioquímico', 'Bioq.', '0', '1'),
    ('6', 'Bioquímica', 'Bioq.', '1', '1');
DELETE t FROM `titulos` t LEFT JOIN `tmp_ref_titulos` r ON t.`id_titulo` = r.`id_titulo`
WHERE r.`id_titulo` IS NULL OR NOT (t.`id_titulo` <=> r.`id_titulo` AND t.`titulo` <=> r.`titulo` AND t.`abreviatura` <=> r.`abreviatura` AND t.`es_femenino` <=> r.`es_femenino` AND t.`activo` <=> r.`activo`);
INSERT INTO `titulos` (`id_titulo`, `titulo`, `abreviatura`, `es_femenino`, `activo`)
SELECT r.`id_titulo`, r.`titulo`, r.`abreviatura`, r.`es_femenino`, r.`activo` FROM `tmp_ref_titulos` r LEFT JOIN `titulos` t ON t.`id_titulo` = r.`id_titulo`
WHERE t.`id_titulo` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_titulos`;
ALTER TABLE `titulos` AUTO_INCREMENT = 7;

-- universidades: 57 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_universidades`;
CREATE TEMPORARY TABLE `tmp_ref_universidades` LIKE `universidades`;
INSERT INTO `tmp_ref_universidades` (`id_universidad`, `id_pais`, `nombre`, `abreviatura`, `activo`) VALUES
    ('1', '9', 'UNIVERSIDAD NACIONAL DE ASUNCIÓN', 'UNA', '1'),
    ('2', '9', 'UNIVERSIDAD NACIONAL DEL ESTE', 'UNE', '1'),
    ('3', '9', 'UNIVERSIDAD NACIONAL DE PILAR', 'UNP', '1'),
    ('4', '9', 'UNIVERSIDAD NACIONAL DE ITAPÚA', 'UNI', '1'),
    ('5', '9', 'UNIVERSIDAD NACIONAL DE CAAGUAZÚ', 'UNCA', '1'),
    ('6', '9', 'UNIVERSIDAD NACIONAL DE CONCEPCIÓN', 'UNC', '1'),
    ('7', '9', 'UNIVERSIDAD NACIONAL DE VILLARRICA DEL ESPÍRITU SANTO', 'UNVES', '1'),
    ('8', '9', 'UNIVERSIDAD CATÓLICA NUESTRA SEÑORA DE LA ASUNCIÓN', 'UCA', '1'),
    ('9', '9', 'UNIVERSIDAD PRIVADA COLUMBIA DEL PARAGUAY', 'UCP', '1'),
    ('10', '9', 'UNIVERSIDAD DEL NORTE', 'UNINORTE', '1'),
    ('11', '9', 'UNIVERSIDAD AUTÓNOMA DE ASUNCIÓN', 'UAA', '1'),
    ('12', '9', 'UNIVERSIDAD PRIVADA DEL ESTE', 'UPE', '1'),
    ('13', '9', 'UNIVERSIDAD AUTÓNOMA DEL PARAGUAY', 'UAP', '1'),
    ('14', '9', 'UNIVERSIDAD COMUNERA', 'UCOM', '1'),
    ('15', '9', 'UNIVERSIDAD AMERICANA', 'UA', '1'),
    ('16', '9', 'UNIVERSIDAD EVANGÉLICA DEL PARAGUAY', 'UEP', '1'),
    ('17', '9', 'UNIVERSIDAD DEL PACÍFICO PRIVADA', 'UP', '1'),
    ('18', '9', 'UNIVERSIDAD TECNOLÓGICA INTERCONTINENTAL', 'UTIC', '1'),
    ('19', '9', 'UNIVERSIDAD TÉCNICA DE COMERCIALIZACIÓN Y DESARROLLO', 'UTCD', '1'),
    ('20', '9', 'UNIVERSIDAD POLITÉCNICA Y ARTÍSTICA', 'UPAP', '1'),
    ('21', '9', 'UNIVERSIDAD DEL CONO SUR DE LAS AMÉRICAS', 'UCSA', '1'),
    ('22', '9', 'UNIVERSIDAD AUTÓNOMA DE LUQUE', 'UAL', '1'),
    ('23', '9', 'UNIVERSIDAD IBEROAMERICANA', 'UNIBE', '1'),
    ('24', '9', 'UNIVERSIDAD METROPOLITANA DE ASUNCIÓN', 'UMA', '1'),
    ('25', '9', 'UNIVERSIDAD DE LA INTEGRACIÓN DE LAS AMÉRICAS', 'UNIDA', '1'),
    ('26', '9', 'UNIVERSIDAD INTERNACIONAL TRES FRONTERAS', 'UNINTER', '1'),
    ('27', '9', 'UNIVERSIDAD SUPERIOR HERNANDO ARIAS DE SAAVEDRA', 'UNISAE', '1'),
    ('28', '9', 'UNIVERSIDAD SAN IGNACIO DE LOYOLA', 'USIL', '1'),
    ('29', '9', 'UNIVERSIDAD LA PAZ', 'ULP', '1'),
    ('30', '9', 'UNIVERSIDAD CENTRAL DEL PARAGUAY', 'UCP', '1'),
    ('31', '9', 'UNIVERSIDAD AUTÓNOMA SAN SEBASTIÁN DE SAN LORENZO', 'USASS', '1'),
    ('32', '9', 'UNIVERSIDAD PRIVADA DEL GUAIRÁ', 'UPG', '1'),
    ('33', '9', 'UNIVERSIDAD NORDESTE DEL PARAGUAY', 'UNDP', '1'),
    ('34', '9', 'UNIVERSIDAD DE DESARROLLO SUSTENTABLE', 'UDS', '1'),
    ('35', '9', 'UNIVERSIDAD SAN CARLOS', 'USC', '1'),
    ('36', '9', 'UNIVERSIDAD DE SAN LORENZO', 'UNISAL', '1'),
    ('37', '9', 'UNIVERSIDAD AUTÓNOMA DE ENCARNACIÓN', 'UNAE', '1'),
    ('38', '9', 'UNIVERSIDAD AUTÓNOMA DEL SUR', 'UNASUR', '1'),
    ('39', '9', 'UNIVERSIDAD HISPANO-GUARANÍ PARA EL DESARROLLO HUMANO', 'UHG', '1'),
    ('40', '9', 'UNIVERSIDAD MARÍA AUXILIADORA', 'UMA', '1'),
    ('41', '9', 'UNIVERSIDAD ESPAÑOLA', 'UE', '1'),
    ('42', '9', 'UNIVERSIDAD LEONARDO DA VINCI', 'ULDV', '1'),
    ('43', '9', 'UNIVERSIDAD NIHON GAKKO', 'UNG', '1'),
    ('44', '9', 'UNIVERSIDAD PRIVADA MARÍA SERRANA', 'UMS', '1'),
    ('45', '9', 'UNIVERSIDAD CENTRO MÉDICO BAUTISTA', 'UCMB', '1'),
    ('46', '9', 'UNIVERSIDAD SANTA CLARA DE ASÍS', 'USCA', '1'),
    ('47', '9', 'UNIVERSIDAD DEL CHACO', 'UNICHACO', '1'),
    ('48', '9', 'UNIVERSIDAD GRAN ASUNCIÓN', 'UNIGRAN', '1'),
    ('49', '9', 'UNIVERSIDAD ADVENTISTA DEL PARAGUAY', 'UAP', '1'),
    ('50', '9', 'UNIVERSIDAD NACIONAL DE CANINDEYÚ', 'UNICAN', '1'),
    ('51', '9', 'UNIVERSIDAD INTERAMERICANA', 'UI', '1'),
    ('52', '9', 'UNIVERSIDAD DEL SOL', 'UNADES', '1'),
    ('53', '9', 'UNIVERSIDAD SUDAMERICANA', 'USUD', '1'),
    ('54', '9', 'UNIVERSIDAD PARAGUAYO-ALEMANA DE CIENCIAS APLICADAS', 'UPA', '1'),
    ('55', '9', 'UNIVERSIDAD POLITÉCNICA TAIWÁN-PARAGUAY', 'UPTP', '1'),
    ('56', '9', 'UNIVERSIDAD NACIONAL DE MISIONES', 'UNAMIS', '1'),
    ('57', '9', 'UNIVERSIDAD JESUITA DEL PARAGUAY', 'UJP', '1');
DELETE t FROM `universidades` t LEFT JOIN `tmp_ref_universidades` r ON t.`id_universidad` = r.`id_universidad`
WHERE r.`id_universidad` IS NULL OR NOT (t.`id_universidad` <=> r.`id_universidad` AND t.`id_pais` <=> r.`id_pais` AND t.`nombre` <=> r.`nombre` AND t.`abreviatura` <=> r.`abreviatura` AND t.`activo` <=> r.`activo`);
INSERT INTO `universidades` (`id_universidad`, `id_pais`, `nombre`, `abreviatura`, `activo`)
SELECT r.`id_universidad`, r.`id_pais`, r.`nombre`, r.`abreviatura`, r.`activo` FROM `tmp_ref_universidades` r LEFT JOIN `universidades` t ON t.`id_universidad` = r.`id_universidad`
WHERE t.`id_universidad` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_universidades`;
ALTER TABLE `universidades` AUTO_INCREMENT = 58;

-- escuelas: 15 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_escuelas`;
CREATE TEMPORARY TABLE `tmp_ref_escuelas` LIKE `escuelas`;
INSERT INTO `tmp_ref_escuelas` (`id_escuela`, `id_pais`, `nombre`, `activo`) VALUES
    ('1', '9', 'ISEPOL', '1'),
    ('2', '1', 'SAD', '0'),
    ('3', '9', 'ACADEMIL', '1'),
    ('4', '9', 'ACADEPOL', '1'),
    ('5', '9', 'COLEPOL', '1'),
    ('6', '9', 'DISERGEMIL', '1'),
    ('7', '9', 'DISERSANFA', '1'),
    ('8', '9', 'LICEMIL', '1'),
    ('9', '9', 'LICEMIL | CARDENAS', '0'),
    ('10', '9', 'LICEMIL | SANTOS', '0'),
    ('11', '9', 'COMISOE', '1'),
    ('12', '9', 'DIGETREN', '1'),
    ('13', '9', 'DROGAS', '1'),
    ('15', '9', 'EFSOA', '1'),
    ('16', '9', 'EMABEL', '1');
DELETE t FROM `escuelas` t LEFT JOIN `tmp_ref_escuelas` r ON t.`id_escuela` = r.`id_escuela`
WHERE r.`id_escuela` IS NULL OR NOT (t.`id_escuela` <=> r.`id_escuela` AND t.`id_pais` <=> r.`id_pais` AND t.`nombre` <=> r.`nombre` AND t.`activo` <=> r.`activo`);
INSERT INTO `escuelas` (`id_escuela`, `id_pais`, `nombre`, `activo`)
SELECT r.`id_escuela`, r.`id_pais`, r.`nombre`, r.`activo` FROM `tmp_ref_escuelas` r LEFT JOIN `escuelas` t ON t.`id_escuela` = r.`id_escuela`
WHERE t.`id_escuela` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_escuelas`;
ALTER TABLE `escuelas` AUTO_INCREMENT = 17;

-- metodos_pago: 4 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_metodos_pago`;
CREATE TEMPORARY TABLE `tmp_ref_metodos_pago` LIKE `metodos_pago`;
INSERT INTO `tmp_ref_metodos_pago` (`id_metodo_pago`, `codigo`, `nombre`, `activo`) VALUES
    ('1', 'EFECTIVO', 'EFECTIVO', '1'),
    ('2', 'TRANSFERENCIA', 'TRANSFERENCIA', '1'),
    ('3', 'TARJETA', 'TARJETA', '1'),
    ('4', 'CHEQUE', 'CHEQUE', '1');
DELETE t FROM `metodos_pago` t LEFT JOIN `tmp_ref_metodos_pago` r ON t.`id_metodo_pago` = r.`id_metodo_pago`
WHERE r.`id_metodo_pago` IS NULL OR NOT (t.`id_metodo_pago` <=> r.`id_metodo_pago` AND t.`codigo` <=> r.`codigo` AND t.`nombre` <=> r.`nombre` AND t.`activo` <=> r.`activo`);
INSERT INTO `metodos_pago` (`id_metodo_pago`, `codigo`, `nombre`, `activo`)
SELECT r.`id_metodo_pago`, r.`codigo`, r.`nombre`, r.`activo` FROM `tmp_ref_metodos_pago` r LEFT JOIN `metodos_pago` t ON t.`id_metodo_pago` = r.`id_metodo_pago`
WHERE t.`id_metodo_pago` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_metodos_pago`;
ALTER TABLE `metodos_pago` AUTO_INCREMENT = 5;

-- plantillas_estudio: 1 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_plantillas_estudio`;
CREATE TEMPORARY TABLE `tmp_ref_plantillas_estudio` LIKE `plantillas_estudio`;
INSERT INTO `tmp_ref_plantillas_estudio` (`id_plantilla`, `id_tipo_estudio`, `codigo`, `nombre`, `version`, `cabecera`, `plantilla`, `calculador`, `activo`) VALUES
    ('1', '1', 'LABORATORIO_MOCK', 'Resultados simulados de laboratorio', '1', '{\"titulo\": \"Informe de laboratorio\", \"es_mock\": true}', '{\"formato\": \"clave-valor\", \"resultados\": {}}', 'resultado_propuesto', '1');
DELETE t FROM `plantillas_estudio` t LEFT JOIN `tmp_ref_plantillas_estudio` r ON t.`id_plantilla` = r.`id_plantilla`
WHERE r.`id_plantilla` IS NULL OR NOT (t.`id_plantilla` <=> r.`id_plantilla` AND t.`id_tipo_estudio` <=> r.`id_tipo_estudio` AND t.`codigo` <=> r.`codigo` AND t.`nombre` <=> r.`nombre` AND t.`version` <=> r.`version` AND t.`cabecera` <=> r.`cabecera` AND t.`plantilla` <=> r.`plantilla` AND t.`calculador` <=> r.`calculador` AND t.`activo` <=> r.`activo`);
INSERT INTO `plantillas_estudio` (`id_plantilla`, `id_tipo_estudio`, `codigo`, `nombre`, `version`, `cabecera`, `plantilla`, `calculador`, `activo`)
SELECT r.`id_plantilla`, r.`id_tipo_estudio`, r.`codigo`, r.`nombre`, r.`version`, r.`cabecera`, r.`plantilla`, r.`calculador`, r.`activo` FROM `tmp_ref_plantillas_estudio` r LEFT JOIN `plantillas_estudio` t ON t.`id_plantilla` = r.`id_plantilla`
WHERE t.`id_plantilla` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_plantillas_estudio`;
ALTER TABLE `plantillas_estudio` AUTO_INCREMENT = 2;

-- postulantes_estudios_referenciales: 10 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_postulantes_estudios_referenciales`;
CREATE TEMPORARY TABLE `tmp_ref_postulantes_estudios_referenciales` LIKE `postulantes_estudios_referenciales`;
INSERT INTO `tmp_ref_postulantes_estudios_referenciales` (`id_estudio`, `estudio_referencial`, `activo`) VALUES
    ('1', 'CARDIOLOGIA', '1'),
    ('2', 'RAYOS X', '1'),
    ('3', 'LABORATORIO - SANGRE', '1'),
    ('4', 'LABORATORIO - ORINA', '1'),
    ('5', 'LABORATORIO - HECES', '1'),
    ('6', 'ELECTROCARDIOGRAMA', '1'),
    ('7', 'ECOGRAFIA', '1'),
    ('8', 'AUDIOMETRIA', '1'),
    ('9', 'ERGO', '1'),
    ('10', 'CLINICA', '1');
DELETE t FROM `postulantes_estudios_referenciales` t LEFT JOIN `tmp_ref_postulantes_estudios_referenciales` r ON t.`id_estudio` = r.`id_estudio`
WHERE r.`id_estudio` IS NULL OR NOT (t.`id_estudio` <=> r.`id_estudio` AND t.`estudio_referencial` <=> r.`estudio_referencial` AND t.`activo` <=> r.`activo`);
INSERT INTO `postulantes_estudios_referenciales` (`id_estudio`, `estudio_referencial`, `activo`)
SELECT r.`id_estudio`, r.`estudio_referencial`, r.`activo` FROM `tmp_ref_postulantes_estudios_referenciales` r LEFT JOIN `postulantes_estudios_referenciales` t ON t.`id_estudio` = r.`id_estudio`
WHERE t.`id_estudio` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_postulantes_estudios_referenciales`;
ALTER TABLE `postulantes_estudios_referenciales` AUTO_INCREMENT = 11;

-- postulantes_arreadores: 3 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_postulantes_arreadores`;
CREATE TEMPORARY TABLE `tmp_ref_postulantes_arreadores` LIKE `postulantes_arreadores`;
INSERT INTO `tmp_ref_postulantes_arreadores` (`id_arreador`, `id_pais`, `nombre`, `institucion`, `telefono`, `correo`, `activo`) VALUES
    ('3', '9', 'GENERAL JUAN CARDENAS', 'MARISCAL', '+595981983476', NULL, '1'),
    ('6', '9', 'VICTOR HUGO SANTOS', 'CAMI', '+59596665259', NULL, '1'),
    ('7', '9', 'PARTICULAR', 'PARTICULAR', NULL, NULL, '1');
DELETE t FROM `postulantes_arreadores` t LEFT JOIN `tmp_ref_postulantes_arreadores` r ON t.`id_arreador` = r.`id_arreador`
WHERE r.`id_arreador` IS NULL OR NOT (t.`id_arreador` <=> r.`id_arreador` AND t.`id_pais` <=> r.`id_pais` AND t.`nombre` <=> r.`nombre` AND t.`institucion` <=> r.`institucion` AND t.`telefono` <=> r.`telefono` AND t.`correo` <=> r.`correo` AND t.`activo` <=> r.`activo`);
INSERT INTO `postulantes_arreadores` (`id_arreador`, `id_pais`, `nombre`, `institucion`, `telefono`, `correo`, `activo`)
SELECT r.`id_arreador`, r.`id_pais`, r.`nombre`, r.`institucion`, r.`telefono`, r.`correo`, r.`activo` FROM `tmp_ref_postulantes_arreadores` r LEFT JOIN `postulantes_arreadores` t ON t.`id_arreador` = r.`id_arreador`
WHERE t.`id_arreador` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_postulantes_arreadores`;
ALTER TABLE `postulantes_arreadores` AUTO_INCREMENT = 8;

-- postulantes_arreador_escuela: 6 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_postulantes_arreador_escuela`;
CREATE TEMPORARY TABLE `tmp_ref_postulantes_arreador_escuela` LIKE `postulantes_arreador_escuela`;
INSERT INTO `tmp_ref_postulantes_arreador_escuela` (`id_arreador_escuela`, `id_arreador`, `id_escuela`, `anio`, `orden_inicio`, `monto_total_hombre`, `monto_total_mujer`, `comision_arreador`, `monto_clinica`, `activo`) VALUES
    ('3', '3', '4', '2026', '1', '1200000.00', '1250000.00', '250000.00', '500000.00', '1'),
    ('6', '7', '4', '2026', '100', '1200000.00', '1250000.00', '0.00', '750000.00', '1'),
    ('7', '7', '5', '2026', '100', '1200000.00', '1250000.00', '0.00', '750000.00', '1'),
    ('8', '3', '5', '2026', '1', '1200000.00', '1250000.00', '250000.00', '500000.00', '1'),
    ('9', '7', '15', '2026', '1', '1200000.00', '1200000.00', '0.00', '700000.00', '1'),
    ('10', '3', '16', '2026', '200', '1450000.00', '1450000.00', '350000.00', '500000.00', '1');
DELETE t FROM `postulantes_arreador_escuela` t LEFT JOIN `tmp_ref_postulantes_arreador_escuela` r ON t.`id_arreador_escuela` = r.`id_arreador_escuela`
WHERE r.`id_arreador_escuela` IS NULL OR NOT (t.`id_arreador_escuela` <=> r.`id_arreador_escuela` AND t.`id_arreador` <=> r.`id_arreador` AND t.`id_escuela` <=> r.`id_escuela` AND t.`anio` <=> r.`anio` AND t.`orden_inicio` <=> r.`orden_inicio` AND t.`monto_total_hombre` <=> r.`monto_total_hombre` AND t.`monto_total_mujer` <=> r.`monto_total_mujer` AND t.`comision_arreador` <=> r.`comision_arreador` AND t.`monto_clinica` <=> r.`monto_clinica` AND t.`activo` <=> r.`activo`);
INSERT INTO `postulantes_arreador_escuela` (`id_arreador_escuela`, `id_arreador`, `id_escuela`, `anio`, `orden_inicio`, `monto_total_hombre`, `monto_total_mujer`, `comision_arreador`, `monto_clinica`, `activo`)
SELECT r.`id_arreador_escuela`, r.`id_arreador`, r.`id_escuela`, r.`anio`, r.`orden_inicio`, r.`monto_total_hombre`, r.`monto_total_mujer`, r.`comision_arreador`, r.`monto_clinica`, r.`activo` FROM `tmp_ref_postulantes_arreador_escuela` r LEFT JOIN `postulantes_arreador_escuela` t ON t.`id_arreador_escuela` = r.`id_arreador_escuela`
WHERE t.`id_arreador_escuela` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_postulantes_arreador_escuela`;
ALTER TABLE `postulantes_arreador_escuela` AUTO_INCREMENT = 11;

-- permisos: 65 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_permisos`;
CREATE TEMPORARY TABLE `tmp_ref_permisos` LIKE `permisos`;
INSERT INTO `tmp_ref_permisos` (`id_permiso`, `codigo`, `nombre`, `descripcion`, `activo`) VALUES
    ('1', 'seguridad.administrar', 'Administrar seguridad', NULL, '1'),
    ('2', 'escuelas.ver', 'Ver escuelas', NULL, '1'),
    ('3', 'escuelas.editar', 'Editar escuelas', NULL, '1'),
    ('4', 'pacientes.ver', 'Ver pacientes', NULL, '1'),
    ('5', 'pacientes.editar', 'Editar pacientes', NULL, '1'),
    ('6', 'estudios.ver', 'Ver estudios', NULL, '1'),
    ('7', 'estudios.editar', 'Editar estudios', NULL, '1'),
    ('8', 'reportes.imprimir', 'Imprimir reportes', NULL, '1'),
    ('9', 'usuarios.ver', 'Ver usuarios', 'Consulta de usuarios y sus roles', '1'),
    ('10', 'usuarios.editar', 'Administrar usuarios', 'Alta, edición, activación y restablecimiento de claves', '1'),
    ('11', 'roles.ver', 'Ver roles', 'Consulta de roles', '1'),
    ('12', 'roles.editar', 'Administrar roles', 'Alta, edición y asignación de permisos', '1'),
    ('13', 'permisos.ver', 'Ver permisos', 'Consulta de permisos', '1'),
    ('14', 'permisos.editar', 'Administrar permisos', 'Alta y edición de permisos', '1'),
    ('15', 'perfil.editar', 'Editar perfil propio', 'Configuración, firma y sello del usuario', '1'),
    ('16', 'menus.ver', 'Ver configuración de menús', 'Consulta del árbol de navegación y sus accesos', '1'),
    ('17', 'menus.editar', 'Administrar menús', 'Crea, organiza y configura la visibilidad de los menús', '1'),
    ('18', 'menu.inicio', 'Ver menú INICIO', 'Permite visualizar el menú inicio', '1'),
    ('19', 'menu.postulantes.referenciales.escuelas', 'Ver menú ESCUELAS', 'Permite visualizar el menú informes.referenciales.escuelas', '1'),
    ('20', 'menu.pacientes', 'Ver menú PACIENTES', 'Permite visualizar el menú pacientes', '1'),
    ('21', 'menu.postulantes', 'Ver menú POSTULANTES', 'Permite visualizar el menú informes', '1'),
    ('22', 'menu.seguridad', 'Ver menú SEGURIDAD', 'Permite visualizar el menú seguridad', '1'),
    ('23', 'menu.seguridad.usuarios', 'Ver menú USUARIOS', 'Permite visualizar el menú seguridad.usuarios', '1'),
    ('24', 'menu.seguridad.roles', 'Ver menú ROLES', 'Permite visualizar el menú seguridad.roles', '1'),
    ('25', 'menu.seguridad.permisos', 'Ver menú PERMISOS', 'Permite visualizar el menú seguridad.permisos', '1'),
    ('26', 'menu.perfil', 'Ver menú MI PERFIL', 'Permite visualizar el menú perfil', '1'),
    ('33', 'menu.seguridad.menus', 'Ver menú MENÚS', 'Permite visualizar el menú seguridad.menus', '1'),
    ('34', 'menu.postulantes.laboratorio', 'Ver menú INFORME LAB', 'Permite visualizar el menú informes.laboratorio', '1'),
    ('35', 'menu.postulantes.postulantes', 'Ver menú POSTULANTES', 'Permite visualizar el menú postulantes.postulantes', '1'),
    ('36', 'universidades.ver', 'Ver universidades', 'Consulta del catálogo de universidades', '1'),
    ('37', 'universidades.editar', 'Administrar universidades', 'Alta, edición y activación de universidades', '1'),
    ('38', 'titulos.ver', 'Ver títulos', 'Consulta del catálogo de títulos profesionales', '1'),
    ('39', 'titulos.editar', 'Administrar títulos', 'Alta, edición y activación de títulos profesionales', '1'),
    ('40', 'menu.referenciales', 'Ver menú REFERENCIALES', 'Permite visualizar el menú referenciales', '1'),
    ('41', 'menu.referenciales.universidades', 'Ver menú UNIVERSIDADES', 'Permite visualizar el menú referenciales.universidades', '1'),
    ('42', 'menu.referenciales.titulos', 'Ver menú TÍTULOS', 'Permite visualizar el menú referenciales.titulos', '1'),
    ('43', 'postulantes_atencion.ver', 'Ver atenciones asignadas', 'Consulta las asignaciones y sus postulantes según el alcance del usuario', '1'),
    ('44', 'postulantes_atencion.asignar', 'Crear asignaciones de atención', 'Crea y edita cabeceras, escuelas, profesionales y estudios', '1'),
    ('45', 'postulantes_atencion.iniciar', 'Iniciar atención de postulantes', 'Habilita o pausa la lista del día para los profesionales', '1'),
    ('46', 'postulantes_atencion.sincronizar', 'Sincronizar postulantes', 'Incorpora postulantes creados en la fecha y escuelas asignadas', '1'),
    ('47', 'postulantes_atencion.atender', 'Atender postulantes', 'Marca la atención y los estudios realizados', '1'),
    ('48', 'postulantes_atencion.llamar', 'Llamar postulantes', 'Permiso histórico: el flujo actual registra directamente la atención', '0'),
    ('49', 'postulantes_atencion.comentar', 'Comentar atenciones', 'Agrega mensajes clínicos, generales o del doctor', '1'),
    ('50', 'postulantes_atencion.pagar', 'Registrar pagos de atención', 'Marca pagos en la cabecera y por postulante', '1'),
    ('51', 'postulantes_atencion.exportar', 'Exportar listas de atención (compatibilidad)', 'La exportación está habilitada para todos los usuarios con acceso a la jornada', '1'),
    ('52', 'postulantes_estudios.ver', 'Ver estudios referenciales', 'Consulta el catálogo de estudios para postulantes', '1'),
    ('53', 'postulantes_estudios.editar', 'Administrar estudios referenciales', 'Crea, edita y activa estudios referenciales', '1'),
    ('54', 'menu.atenciones', 'Ver menú ATENCIONES', 'Permite visualizar el menú atenciones', '1'),
    ('55', 'menu.postulantes.gestion', 'Ver menú GESTIÓN DE ATENCIONES', 'Permite visualizar el menú informes.gestion', '1'),
    ('56', 'menu.referenciales.estudios', 'Ver menú ESTUDIOS POSTULANTES', 'Permite visualizar el menú referenciales.estudios', '1'),
    ('57', 'postulantes_atencion.estado', 'Activar y desactivar asignaciones', 'Oculta asignaciones activas y permite restaurarlas desde la sección de desactivadas', '1'),
    ('58', 'reportes.rx.imprimir', 'Imprimir informes RX', 'Busca postulantes y genera informes radiológicos individuales o masivos', '1'),
    ('59', 'menu.postulantes.rx', 'Ver menú INFORME RX', 'Permite visualizar el menú informes.rx', '1'),
    ('60', 'reportes.eco.imprimir', 'Imprimir informes ECO', 'Busca postulantes y genera informes de ecografía por tipo de estudio', '1'),
    ('61', 'reportes.eco.editar', 'Editar resultados ECO', 'Edita y guarda los resultados individuales de los informes de ecografía', '1'),
    ('62', 'menu.postulantes.eco', 'Ver menú INFORME ECO', 'Permite visualizar el módulo INFORME ECO dentro de POSTULANTES', '1'),
    ('63', 'reportes.audiometria.imprimir', 'Imprimir informes de audiometría', 'Busca postulantes y genera informes de audiometría', '1'),
    ('64', 'reportes.audiometria.editar', 'Editar DX de audiometría', 'Edita y guarda el diagnóstico de los informes de audiometría', '1'),
    ('65', 'menu.postulantes.audiometria', 'Ver menú INFORME AUDIO', 'Permite visualizar el menú informes.audiometria', '1'),
    ('66', 'reportes.rotulos.imprimir', 'Imprimir rótulos de postulantes', 'Busca postulantes por escuela y fecha y genera rótulos individuales o masivos', '1'),
    ('67', 'menu.postulantes.rotulos', 'Ver menú IMPRIMIR RÓTULO', 'Permite visualizar el módulo IMPRIMIR RÓTULO dentro de POSTULANTES', '1'),
    ('68', 'menu.postulantes.referenciales.estudios', 'Ver menú ESTUDIOS', 'Permite visualizar el menú informes.referenciales.estudios', '1'),
    ('69', 'menu.postulantes.arreadores', 'Ver menú CONVENIOS', 'Permite visualizar el menú informes.arreadores', '1'),
    ('70', 'menu.postulantes.referenciales', 'Ver menú REFERENCIALES', 'Permite visualizar el menú informes.referenciales', '1'),
    ('71', 'dashboard.ver', 'Ver datos del panel principal', 'Indicadores, cobros, ganancias y gráficos del panel principal', '1');
DELETE t FROM `permisos` t LEFT JOIN `tmp_ref_permisos` r ON t.`id_permiso` = r.`id_permiso`
WHERE r.`id_permiso` IS NULL OR NOT (t.`id_permiso` <=> r.`id_permiso` AND t.`codigo` <=> r.`codigo` AND t.`nombre` <=> r.`nombre` AND t.`descripcion` <=> r.`descripcion` AND t.`activo` <=> r.`activo`);
INSERT INTO `permisos` (`id_permiso`, `codigo`, `nombre`, `descripcion`, `activo`)
SELECT r.`id_permiso`, r.`codigo`, r.`nombre`, r.`descripcion`, r.`activo` FROM `tmp_ref_permisos` r LEFT JOIN `permisos` t ON t.`id_permiso` = r.`id_permiso`
WHERE t.`id_permiso` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_permisos`;
ALTER TABLE `permisos` AUTO_INCREMENT = 72;

-- roles: 6 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_roles`;
CREATE TEMPORARY TABLE `tmp_ref_roles` LIKE `roles`;
INSERT INTO `tmp_ref_roles` (`id_rol`, `codigo`, `nombre`, `descripcion`, `activo`) VALUES
    ('1', 'ADMIN', 'Super Administrador', 'Acceso total al sistema', '1'),
    ('2', 'PROFESIONAL', 'Profesional de salud', 'Gestiona estudios y resultados', '1'),
    ('3', 'RECEPCION', 'Recepción', 'Gestiona pacientes y derivaciones', '1'),
    ('4', 'ATENDER_POSTULANTES', 'Atencion de Postulantes', 'El que se encarga de atender al postulante, en el dia', '1'),
    ('5', 'INFO_ECO_POSTULANTES', 'Informe de Ecografia - Postulantes', 'Ve y Edita informe de ecografías de postulantes', '1'),
    ('6', 'INFO_AUDIO_POSTULANTES', 'Informe de Audiometria - Postulantes', 'Ve y Edita informe de audiometrías de postulantes', '1');
DELETE t FROM `roles` t LEFT JOIN `tmp_ref_roles` r ON t.`id_rol` = r.`id_rol`
WHERE r.`id_rol` IS NULL OR NOT (t.`id_rol` <=> r.`id_rol` AND t.`codigo` <=> r.`codigo` AND t.`nombre` <=> r.`nombre` AND t.`descripcion` <=> r.`descripcion` AND t.`activo` <=> r.`activo`);
INSERT INTO `roles` (`id_rol`, `codigo`, `nombre`, `descripcion`, `activo`)
SELECT r.`id_rol`, r.`codigo`, r.`nombre`, r.`descripcion`, r.`activo` FROM `tmp_ref_roles` r LEFT JOIN `roles` t ON t.`id_rol` = r.`id_rol`
WHERE t.`id_rol` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_roles`;
ALTER TABLE `roles` AUTO_INCREMENT = 7;

-- rol_permisos: 123 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_rol_permisos`;
CREATE TEMPORARY TABLE `tmp_ref_rol_permisos` LIKE `rol_permisos`;
INSERT INTO `tmp_ref_rol_permisos` (`id_rol`, `id_permiso`) VALUES
    ('1', '1'),
    ('1', '2'),
    ('1', '3'),
    ('1', '4'),
    ('1', '5'),
    ('1', '6'),
    ('1', '7'),
    ('1', '8'),
    ('1', '9'),
    ('1', '10'),
    ('1', '11'),
    ('1', '12'),
    ('1', '13'),
    ('1', '14'),
    ('1', '15'),
    ('1', '16'),
    ('1', '17'),
    ('1', '18'),
    ('1', '19'),
    ('1', '20'),
    ('1', '21'),
    ('1', '22'),
    ('1', '23'),
    ('1', '24'),
    ('1', '25'),
    ('1', '26'),
    ('1', '33'),
    ('1', '34'),
    ('1', '35'),
    ('1', '36'),
    ('1', '37'),
    ('1', '38'),
    ('1', '39'),
    ('1', '40'),
    ('1', '41'),
    ('1', '42'),
    ('1', '43'),
    ('1', '44'),
    ('1', '45'),
    ('1', '46'),
    ('1', '47'),
    ('1', '48'),
    ('1', '49'),
    ('1', '50'),
    ('1', '51'),
    ('1', '52'),
    ('1', '53'),
    ('1', '54'),
    ('1', '55'),
    ('1', '56'),
    ('1', '57'),
    ('1', '58'),
    ('1', '59'),
    ('1', '60'),
    ('1', '61'),
    ('1', '62'),
    ('1', '63'),
    ('1', '64'),
    ('1', '65'),
    ('1', '66'),
    ('1', '67'),
    ('1', '68'),
    ('1', '69'),
    ('1', '70'),
    ('1', '71'),
    ('2', '4'),
    ('2', '6'),
    ('2', '7'),
    ('2', '8'),
    ('2', '15'),
    ('2', '21'),
    ('2', '26'),
    ('2', '34'),
    ('2', '35'),
    ('2', '58'),
    ('2', '60'),
    ('2', '61'),
    ('2', '62'),
    ('2', '63'),
    ('2', '64'),
    ('2', '66'),
    ('2', '67'),
    ('2', '69'),
    ('3', '2'),
    ('3', '3'),
    ('3', '4'),
    ('3', '5'),
    ('3', '8'),
    ('3', '15'),
    ('3', '21'),
    ('3', '26'),
    ('3', '34'),
    ('3', '35'),
    ('3', '58'),
    ('3', '60'),
    ('3', '61'),
    ('3', '62'),
    ('3', '63'),
    ('3', '64'),
    ('3', '66'),
    ('3', '67'),
    ('3', '69'),
    ('4', '15'),
    ('4', '21'),
    ('4', '26'),
    ('4', '43'),
    ('4', '46'),
    ('4', '47'),
    ('4', '48'),
    ('4', '49'),
    ('4', '55'),
    ('5', '2'),
    ('5', '4'),
    ('5', '21'),
    ('5', '60'),
    ('5', '61'),
    ('5', '62'),
    ('6', '2'),
    ('6', '4'),
    ('6', '21'),
    ('6', '63'),
    ('6', '64'),
    ('6', '65');
DELETE t FROM `rol_permisos` t LEFT JOIN `tmp_ref_rol_permisos` r ON t.`id_rol` = r.`id_rol` AND t.`id_permiso` = r.`id_permiso`
WHERE r.`id_rol` IS NULL OR NOT (t.`id_rol` <=> r.`id_rol` AND t.`id_permiso` <=> r.`id_permiso`);
INSERT INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT r.`id_rol`, r.`id_permiso` FROM `tmp_ref_rol_permisos` r LEFT JOIN `rol_permisos` t ON t.`id_rol` = r.`id_rol` AND t.`id_permiso` = r.`id_permiso`
WHERE t.`id_rol` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_rol_permisos`;

-- menus: 25 fila(s)
DROP TEMPORARY TABLE IF EXISTS `tmp_ref_menus`;
CREATE TEMPORARY TABLE `tmp_ref_menus` LIKE `menus`;
INSERT INTO `tmp_ref_menus` (`id_menu`, `id_menu_padre`, `id_permiso`, `codigo`, `etiqueta`, `url`, `icono`, `orden`, `visible_todos`, `activo`) VALUES
    ('1', NULL, '18', 'inicio', 'INICIO', 'index.php', 'inicio', '10', '1', '1'),
    ('2', '25', '19', 'postulantes.referenciales.escuelas', 'ESCUELAS', 'designaciones.php', 'escuela', '10', '0', '1'),
    ('3', NULL, '20', 'pacientes', 'PACIENTES', 'pacientes.php', 'paciente', '30', '0', '0'),
    ('4', NULL, '21', 'postulantes', 'POSTULANTES', NULL, 'postulantes', '40', '0', '1'),
    ('5', NULL, '22', 'seguridad', 'SEGURIDAD', NULL, 'escudo', '50', '0', '1'),
    ('6', '5', '23', 'seguridad.usuarios', 'USUARIOS', 'usuarios.php', 'usuarios', '10', '0', '1'),
    ('7', '5', '24', 'seguridad.roles', 'ROLES', 'roles.php', 'credencial', '20', '0', '1'),
    ('8', '5', '25', 'seguridad.permisos', 'PERMISOS', 'permisos.php', 'llave', '30', '0', '1'),
    ('9', NULL, '26', 'perfil', 'MI PERFIL', 'perfil.php', 'usuario', '60', '1', '1'),
    ('10', '5', '33', 'seguridad.menus', 'MENÚS', 'menus.php', 'menu', '40', '0', '1'),
    ('11', '4', '34', 'postulantes.laboratorio', 'INFORME LAB', 'informe_lab.php', 'laboratorio', '50', '0', '1'),
    ('12', '4', '35', 'postulantes.postulantes', 'POSTULANTES', 'postulantes.php', 'lista', '40', '0', '1'),
    ('13', NULL, '40', 'referenciales', 'REFERENCIALES', NULL, 'libro', '45', '0', '1'),
    ('14', '13', '41', 'referenciales.universidades', 'UNIVERSIDADES', 'universidades.php', 'graduacion', '10', '0', '1'),
    ('15', '13', '42', 'referenciales.titulos', 'TÍTULOS', 'titulos.php', 'titulo', '20', '0', '1'),
    ('16', NULL, '54', 'atenciones', 'ATENCIONES', NULL, 'estetoscopio', '35', '0', '0'),
    ('17', '4', '55', 'postulantes.gestion', 'GESTIÓN DE ATENCIONES', 'atenciones.php', 'portapapeles', '100', '0', '1'),
    ('18', '13', '56', 'referenciales.estudios', 'ESTUDIOS POSTULANTES', 'estudios_postulantes.php', 'documento', '20', '0', '0'),
    ('19', '4', '59', 'postulantes.rx', 'INFORME RX', 'informe_rx.php', 'rayos', '60', '0', '1'),
    ('20', '4', '62', 'postulantes.eco', 'INFORME ECO', 'informe_eco.php', 'onda', '70', '0', '1'),
    ('21', '4', '65', 'postulantes.audiometria', 'INFORME AUDIO', 'informe_audiometria.php', 'audio', '80', '0', '1'),
    ('22', '4', '67', 'postulantes.rotulos', 'IMPRIMIR RÓTULO', 'rotulos.php', 'etiqueta', '90', '0', '1'),
    ('23', '25', '68', 'postulantes.referenciales.estudios', 'ESTUDIOS', 'estudios_postulantes.php', 'documento', '20', '0', '1'),
    ('24', '4', '69', 'postulantes.arreadores', 'CONVENIOS', 'arreadores.php', 'convenio', '30', '0', '1'),
    ('25', '4', '70', 'postulantes.referenciales', 'REFERENCIALES', NULL, 'carpeta', '10', '0', '1');
DELETE t FROM `menus` t LEFT JOIN `tmp_ref_menus` r ON t.`id_menu` = r.`id_menu`
WHERE r.`id_menu` IS NULL OR NOT (t.`id_menu` <=> r.`id_menu` AND t.`id_menu_padre` <=> r.`id_menu_padre` AND t.`id_permiso` <=> r.`id_permiso` AND t.`codigo` <=> r.`codigo` AND t.`etiqueta` <=> r.`etiqueta` AND t.`url` <=> r.`url` AND t.`icono` <=> r.`icono` AND t.`orden` <=> r.`orden` AND t.`visible_todos` <=> r.`visible_todos` AND t.`activo` <=> r.`activo`);
INSERT INTO `menus` (`id_menu`, `id_menu_padre`, `id_permiso`, `codigo`, `etiqueta`, `url`, `icono`, `orden`, `visible_todos`, `activo`)
SELECT r.`id_menu`, r.`id_menu_padre`, r.`id_permiso`, r.`codigo`, r.`etiqueta`, r.`url`, r.`icono`, r.`orden`, r.`visible_todos`, r.`activo` FROM `tmp_ref_menus` r LEFT JOIN `menus` t ON t.`id_menu` = r.`id_menu`
WHERE t.`id_menu` IS NULL;
DROP TEMPORARY TABLE `tmp_ref_menus`;
ALTER TABLE `menus` AUTO_INCREMENT = 26;

SET FOREIGN_KEY_CHECKS = @fk_checks_previo;
