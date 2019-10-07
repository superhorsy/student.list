--
-- Скрипт сгенерирован Devart dbForge Studio for MySQL, Версия 7.4.201.0
-- Домашняя страница продукта: http://www.devart.com/ru/dbforge/mysql/studio
-- Дата скрипта: 07.10.2019 18:57:29
-- Версия сервера: 5.6.41
-- Версия клиента: 4.1
--

-- 
-- Отключение внешних ключей
-- 
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- 
-- Установить режим SQL (SQL mode)
-- 
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 
-- Установка кодировки, с использованием которой клиент будет посылать запросы на сервер
--
SET NAMES 'utf8';

--
-- Установка базы данных по умолчанию
--
USE tournament;

--
-- Удалить таблицу `players`
--
DROP TABLE IF EXISTS players;

--
-- Удалить таблицу `tournament`
--
DROP TABLE IF EXISTS tournament;

--
-- Удалить таблицу `user`
--
DROP TABLE IF EXISTS user;

--
-- Установка базы данных по умолчанию
--
USE tournament;

--
-- Создать таблицу `user`
--
CREATE TABLE user (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(20) NOT NULL,
  name varchar(50) NOT NULL,
  email varchar(320) NOT NULL,
  hash varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 15,
AVG_ROW_LENGTH = 16384,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать индекс `user_login_uindex` для объекта типа таблица `user`
--
ALTER TABLE user
ADD UNIQUE INDEX user_login_uindex (username);

--
-- Создать таблицу `tournament`
--
CREATE TABLE tournament (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  date date DEFAULT NULL,
  owner_id int(11) NOT NULL,
  status enum ('awaiting', 'in progress', 'ended') NOT NULL DEFAULT 'awaiting',
  current_round int(11) DEFAULT NULL,
  round_count int(11) DEFAULT NULL,
  toss blob DEFAULT NULL COMMENT 'serilization of TOSS method result',
  prize_pool int(11) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 14,
AVG_ROW_LENGTH = 8192,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать внешний ключ
--
ALTER TABLE tournament
ADD CONSTRAINT tournament_user_id_fk FOREIGN KEY (owner_id)
REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Создать таблицу `players`
--
CREATE TABLE players (
  id int(11) NOT NULL AUTO_INCREMENT,
  team varchar(50) DEFAULT NULL,
  nickname varchar(50) NOT NULL,
  tournament_id int(11) DEFAULT NULL,
  lifes int(1) DEFAULT 2,
  is_suspended tinyint(1) DEFAULT 0,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 141,
AVG_ROW_LENGTH = 682,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать внешний ключ
--
ALTER TABLE players
ADD CONSTRAINT players_tournament_id_fk FOREIGN KEY (tournament_id)
REFERENCES tournament (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Вывод данных для таблицы user
--
INSERT INTO user VALUES
(14, 'test', 'test', 'test@test.ru', '$2y$10$.S9UL//SxnMQWp.5K867fumxf0oiK1.SlAEe0EMvplOuIRrGdknva');

-- 
-- Вывод данных для таблицы tournament
--
INSERT INTO tournament VALUES
(8, 'Tournament', '2019-10-14', 14, 'ended', 4, 4, x'7B2241223A5B22416E74692D4D616765222C224172632057617264656E225D7D', NULL),
(13, 'Test1', '2019-10-10', 14, 'awaiting', NULL, NULL, x'6E756C6C', NULL);

-- 
-- Вывод данных для таблицы players
--
INSERT INTO players VALUES
(7, 'OUT', 'Женя', 8, 0, 0),
(8, 'OUT', 'Андрей', 8, 0, 0),
(9, 'OUT', 'Player3', 8, 0, 0),
(10, 'Arc Warden', 'Player4', 8, 1, 0),
(11, 'OUT', 'Player5', 8, 0, 0),
(12, 'OUT', 'Player6', 8, 0, 0),
(13, 'Arc Warden', 'Player7', 8, 1, 0),
(14, 'OUT', 'Player8', 8, 0, 0),
(15, 'Arc Warden', 'Player9', 8, 1, 0),
(16, 'OUT', 'Player10', 8, 0, 0),
(17, 'Anti-Mage', 'Player11', 8, 1, 0),
(18, 'WAIT', 'Player12', 8, 2, 0),
(19, 'OUT', 'Player13', 8, 0, 0),
(20, 'OUT', 'Player14', 8, 0, 0),
(21, 'OUT', 'Player15', 8, 0, 0),
(22, 'OUT', 'Player16', 8, 0, 0),
(23, 'Anti-Mage', 'Player17', 8, 1, 0),
(24, 'OUT', 'Player18', 8, 0, 0),
(25, 'Arc Warden', 'Player19', 8, 2, 0),
(26, 'Arc Warden', 'Player20', 8, 2, 0),
(27, 'OUT', 'Player21', 8, 0, 0),
(28, 'OUT', 'Player22', 8, 0, 0),
(139, NULL, 'asdf', 13, 2, 0),
(140, NULL, '1234', 13, 2, 0);

-- 
-- Восстановить предыдущий режим SQL (SQL mode)
-- 
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

-- 
-- Включение внешних ключей
-- 
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;