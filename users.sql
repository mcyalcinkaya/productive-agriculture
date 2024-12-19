-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 19 Ara 2024, 08:49:05
-- Sunucu sürümü: 10.4.27-MariaDB
-- PHP Sürümü: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `agriculture_project`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `assigned_area` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `assigned_area`) VALUES
(1, 'farmer_5078', '$2y$10$evKX.leCIpMwcs8zyHqe0ubeEK4Ex4ui.TzoPyHmUnbeJtQ7YyRQW', 500),
(2, 'farmer_2604', '$2y$10$znBNRcFTQnDi.MaT/j/wzeJfHhhoRX3k9S6waH2EmzpMklua3jL1W', 230),
(3, 'farmer_8675', '$2y$10$gag.F5U26VqBskJzSjLm2OUOumzXmeNj6EOQnaTLxlXdzOCcXNnhG', 340),
(4, 'farmer_2773', '$2y$10$Jya4I2PqJbyZ.RJH/GP8dOIYBZzT5a5N2Tsm.l3sPcacdy6LzlN3G', -1),
(5, 'farmer_3310', '$2y$10$MW3P7DoIZKsDVbGp/ypiW.7/jO0W/J2bP.bV7bEfK5SZmv1fq2Ltu', 50);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
