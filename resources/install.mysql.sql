CREATE TABLE IF NOT EXISTS cms_shop_cart_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  form_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Form ID#',
  no_amount TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Single item of a type',
  interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Interface ID#',
  description TEXT NULL DEFAULT NULL COMMENT 'E-mail template',

  PRIMARY KEY (id),
  KEY (form_id)
) COMMENT 'Cart types';


CREATE TABLE IF NOT EXISTS cms_shop_cart_types_material_types_assoc (
  ctype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cart type ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  price_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Price field ID#',
  price_callback TEXT NULL DEFAULT NULL COMMENT 'Price callback',

  PRIMARY KEY (ctype, mtype),
  KEY (ctype),
  KEY (mtype),
  KEY (price_id)
) COMMENT 'Cart types to material types association';


CREATE TABLE IF NOT EXISTS cms_shop_orders_statuses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Priority',

  PRIMARY KEY (id),
  INDEX (priority)
) COMMENT 'Orders statuses';


CREATE TABLE IF NOT EXISTS cms_shop_orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Site user ID#',
  pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cart type ID#',
  page_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Page ID#',
  post_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Post date',
  vis TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Visited',
  ip VARCHAR(255) NOT NULL DEFAULT '0.0.0.0' COMMENT 'IP address',
  user_agent VARCHAR(255) NOT NULL DEFAULT '0.0.0.0' COMMENT 'User Agent',
  
  PRIMARY KEY (id),
  KEY (uid),
  KEY (pid),
  KEY (page_id)
) COMMENT 'Orders';


CREATE TABLE IF NOT EXISTS cms_shop_orders_goods (
  order_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order ID#',
  material_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material ID#',
  meta VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'Meta data',
  realprice DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Real price',
  amount INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Amount',
  total_sum DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total sum',

  PRIMARY KEY (order_id, material_id, meta),
  KEY (order_id),
  KEY (material_id),
  KEY (meta)
) COMMENT 'Orders goods';


CREATE TABLE IF NOT EXISTS cms_shop_orders_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  order_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order ID#',
  status_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Status ID#',
  post_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Status post date',
  description TEXT NULL DEFAULT NULL COMMENT 'Description',

  PRIMARY KEY (id),
  KEY (order_id),
  KEY (status_id),
  KEY (post_date)
) COMMENT 'Orders history';


CREATE TABLE IF NOT EXISTS cms_shop_priceloaders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  ufid VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Unique field ID#',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  std_interface TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Standard interface',
  description TEXT NULL DEFAULT NULL COMMENT 'Interface code',

  PRIMARY KEY (id),
  KEY (mtype),
  KEY (ufid)
) COMMENT 'Price loaders';


CREATE TABLE IF NOT EXISTS cms_shop_priceloaders_columns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Price loader ID#',
  callback TEXT NULL DEFAULT NULL COMMENT 'Callback code',
  priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Priority',

  PRIMARY KEY (id),
  KEY (pid),
  INDEX (priority)
) COMMENT 'Price loaders columns';


CREATE TABLE IF NOT EXISTS cms_shop_imageloaders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  ufid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unique field ID#',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  sep_string VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Separator string',
  std_interface TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Standard interface',
  description TEXT NULL DEFAULT NULL COMMENT 'Interface code',

  PRIMARY KEY (id),
  KEY (mtype),
  KEY (ufid)
) COMMENT 'Image loaders';


CREATE TABLE IF NOT EXISTS cms_shop_blocks_cart (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID#',
  cart_type int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Cart type ID#',
  PRIMARY KEY (id),
  KEY cart_type (cart_type)
) COMMENT='Cart blocks';
