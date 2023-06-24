--
-- Estructura de tabla para la tabla `bot_sw`
--

CREATE TABLE `bot_sw` (
  `idbotsw` int NOT NULL,
  `idusuario` int NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `symbol_estable` varchar(14) NOT NULL,
  `symbol_reserva` varchar(14) NOT NULL,
  `estado` int NOT NULL DEFAULT '0',
  `open_orders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bot_sw_capital_log`
--

CREATE TABLE `bot_sw_capital_log` (
  `idbotsw` int NOT NULL,
  `symbol` varchar(14) NOT NULL,
  `qty` decimal(15,8) NOT NULL,
  `price` decimal(15,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bot_sw_orden_log`
--

CREATE TABLE `bot_sw_orden_log` (
  `idbotsw` int NOT NULL,
  `datetime` datetime NOT NULL,
  `base_asset` varchar(14) NOT NULL,
  `quote_asset` varchar(14) NOT NULL,
  `side` int NOT NULL,
  `origQty` decimal(15,8) NOT NULL,
  `price` decimal(15,8) NOT NULL,
  `orderId` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bot_sw`
--
ALTER TABLE `bot_sw`
  ADD PRIMARY KEY (`idbotsw`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indices de la tabla `bot_sw_capital_log`
--
ALTER TABLE `bot_sw_capital_log`
  ADD KEY `idbotsw` (`idbotsw`);

--
-- Indices de la tabla `bot_sw_orden_log`
--
ALTER TABLE `bot_sw_orden_log`
  ADD KEY `idbotsw` (`idbotsw`);