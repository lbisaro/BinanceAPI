
CREATE TABLE `wallet` (
  `idusuario` int(10) NOT NULL,
  `date` date NOT NULL,
  `open` decimal(12,2) NOT NULL,
  `high` decimal(12,2) NOT NULL,
  `low` decimal(12,2) NOT NULL,
  `close` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `wallet`
--
ALTER TABLE `wallet`
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `date` (`date`);
