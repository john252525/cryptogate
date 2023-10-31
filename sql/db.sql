-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Окт 25 2023 г., 17:35
-- Версия сервера: 5.6.51
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `task2`
--

-- --------------------------------------------------------

--
-- Структура таблицы `deal`
--

CREATE TABLE `deal` (
  `id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL DEFAULT '',
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `count_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `order_binance`
--

CREATE TABLE `order_binance` (
  `id` int(11) NOT NULL,
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `preorder_id` int(11) NOT NULL DEFAULT '0',
  `stock_id` int(11) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `stock_order_id_1` int(11) NOT NULL DEFAULT '0',
  `stock_order_id_2` int(11) NOT NULL DEFAULT '0',
  `state` varchar(255) NOT NULL DEFAULT '',
  `dt_upd` varchar(19) NOT NULL DEFAULT '',
  `ts_upd` int(11) NOT NULL DEFAULT '0',
  `dt_check` varchar(19) NOT NULL DEFAULT '',
  `ts_check` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `order_binance_log`
--

CREATE TABLE `order_binance_log` (
  `id` int(11) NOT NULL,
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `stock_id` int(11) NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL DEFAULT '',
  `request` text NOT NULL,
  `data` text NOT NULL,
  `stock_order_id_1` int(11) NOT NULL DEFAULT '0',
  `stock_order_id_2` int(11) NOT NULL DEFAULT '0',
  `state` int(11) NOT NULL DEFAULT '0',
  `weight_ip` int(11) NOT NULL DEFAULT '0',
  `weight_uid` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `preorder`
--

CREATE TABLE `preorder` (
  `id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL DEFAULT '',
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `deal_id` int(11) NOT NULL DEFAULT '-1',
  `stock_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL DEFAULT '',
  `side` varchar(4) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `positionSide` varchar(5) NOT NULL DEFAULT '',
  `pair` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `data` text NOT NULL,
  `state` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `stock` varchar(255) NOT NULL DEFAULT '',
  `apikey` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `stock`
--

INSERT INTO `stock` (`id`, `dt_ins`, `ts_ins`, `user_id`, `stock`, `apikey`) VALUES
(1, '', 0, 10, 'binance_spot', '');

-- --------------------------------------------------------

--
-- Структура таблицы `task`
--

CREATE TABLE `task` (
  `id` int(11) NOT NULL,
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `preorder_id` int(11) NOT NULL DEFAULT '0',
  `action` varchar(6) NOT NULL DEFAULT '',
  `mode` varchar(5) NOT NULL DEFAULT '',
  `state` int(11) NOT NULL DEFAULT '0',
  `dt_upd` varchar(19) NOT NULL DEFAULT '',
  `ts_upd` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `dt_ins` varchar(19) NOT NULL DEFAULT '',
  `ts_ins` int(11) NOT NULL DEFAULT '0',
  `token` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `user`
--

INSERT INTO `user` (`id`, `dt_ins`, `ts_ins`, `token`) VALUES
(10, '', 0, '555aaa');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `deal`
--
ALTER TABLE `deal`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `order_binance`
--
ALTER TABLE `order_binance`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `order_binance_log`
--
ALTER TABLE `order_binance_log`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `preorder`
--
ALTER TABLE `preorder`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deal_id` (`deal_id`);

--
-- Индексы таблицы `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `stock` (`stock`(191));

--
-- Индексы таблицы `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`(191));

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `deal`
--
ALTER TABLE `deal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_binance`
--
ALTER TABLE `order_binance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_binance_log`
--
ALTER TABLE `order_binance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `preorder`
--
ALTER TABLE `preorder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `task`
--
ALTER TABLE `task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
