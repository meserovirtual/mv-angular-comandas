
# COMANDAS
CREATE TABLE comandas (
  comanda_id int(11) NOT NULL AUTO_INCREMENT,
  fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mesa_id VARCHAR()45 NOT NULL,
  origen_id int(11) NOT NULL,
  status int(11) DEFAULT NULL COMMENT '1-Creada, 2-Servida, 3-Pedida, 4-Cancelada, 5-Cerrada',
  total DECIMAL (10,2) DEFAULT 0.00,
  usuario_id int(11) NOT NULL,
  envio_id int(11) NOT NULL,
  PRIMARY KEY (comanda_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# COMANDAS DETALLES
CREATE TABLE comandas_detalles (
  comanda_detalle_id int(11) NOT NULL AUTO_INCREMENT,
  producto_id int(11) NOT NULL,
  status int(11) NOT NULL DEFAULT 0,
  preparacion_inicio timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  preparacion_fin timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  comentarios varchar(200) DEFAULT '',
  comanda_id int(11) NOT NULL,
  cantidad int(11) NOT NULL,
  precio DECIMAL (10,2) NOT NULL DEFAULT 0.00,
  usuario_id int(11) NOT NULL,
  session_id varchar(20) NOT NULL,
  PRIMARY KEY (comanda_detalle_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# COMANDAS EXTRAS
CREATE TABLE comandas_extras (
  comanda_extra_id int(11) NOT NULL AUTO_INCREMENT,
  comanda_detalle_id int(11) NOT NULL,
  cantidad int(11) DEFAULT '-1',
  precio DECIMAL (10,2) DEFAULT 0.00,
  producto_id int(11) DEFAULT '-1',
  PRIMARY KEY (comanda_extra_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# RESERVAS
CREATE TABLE reservas (
  reserva_id int(11) NOT NULL AUTO_INCREMENT,
  comanda_id int(11) NOT NULL,
  sucursal_id int(11) DEFAULT '-1',
  comensales int(11) DEFAULT '1',
  fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pagado int(1) DEFAULT 0 COMMENT '0-No Pagada, 1-Pagada',
  PRIMARY KEY (reserva_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

