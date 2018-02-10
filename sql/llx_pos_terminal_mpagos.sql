CREATE TABLE IF NOT EXISTS `llx_pos_terminal_mpagos` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_terminal` int(11) NOT NULL,
  `fk_modepay` int(11) NOT NULL,
  `fk_bankacount` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
);
