CREATE TABLE `llx_pos_cashgroup_cash` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `fk_cashgroup` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `permiso` int(1) NOT NULL DEFAULT '1',
  `pmixtos` int(1) NOT NULL DEFAULT '1',
  `pcproducto` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB;
