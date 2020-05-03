CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_cart_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  form_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Form ID#',
  no_amount TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Single item of a type',

  PRIMARY KEY (id),
  KEY (form_id)
) COMMENT 'Cart types';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_cart_types_material_types_assoc (
  ctype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cart type ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  price_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Price field ID#',
  price_callback TEXT NULL DEFAULT NULL COMMENT 'Price callback',

  PRIMARY KEY (ctype, mtype),
  KEY (ctype),
  KEY (mtype),
  KEY (price_id)
) COMMENT 'Cart types to material types association';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_orders_statuses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  do_notify TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Notify user',
  notification_title TEXT NULL DEFAULT NULL COMMENT 'User notification title',
  notification TEXT NULL DEFAULT NULL COMMENT 'User notification',
  priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Priority',

  PRIMARY KEY (id),
  INDEX (priority)
) COMMENT 'Orders statuses';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Site user ID#',
  pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cart type ID#',
  page_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Page ID#',
  post_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Post date',
  vis INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Visited',
  ip VARCHAR(255) NOT NULL DEFAULT '0.0.0.0' COMMENT 'IP address',
  user_agent VARCHAR(255) NOT NULL DEFAULT '0.0.0.0' COMMENT 'User Agent',
  status_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Status ID#',
  paid TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Payment status',
  payment_interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Payment interface ID#',
  payment_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Payment ID#',
  payment_url VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Payment URL',
  
  PRIMARY KEY (id),
  KEY (uid),
  KEY (pid),
  KEY (page_id),
  KEY (status_id),
  INDEX (paid),
  KEY (payment_interface_id),
  INDEX (payment_id)
) COMMENT 'Orders';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_orders_goods (
  order_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order ID#',
  material_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material ID#',
  name VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'Name',
  meta VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'Meta data',
  realprice DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT 'Real price',
  amount INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Amount',
  priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Priority',

  PRIMARY KEY (order_id, material_id, meta),
  KEY (order_id),
  KEY (material_id),
  KEY (meta),
  INDEX (priority)
) COMMENT 'Orders goods';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_orders_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Author ID#',
  order_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order ID#',
  status_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Status ID#',
  paid TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Payment status',
  post_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Status post date',
  description TEXT NULL DEFAULT NULL COMMENT 'Description',

  PRIMARY KEY (id),
  KEY (uid),
  KEY (order_id),
  KEY (status_id),
  INDEX (paid),
  KEY (post_date)
) COMMENT 'Orders history';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_priceloaders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  ufid VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Unique field ID#',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN',
  interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Interface ID#',
  rows INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Rows from top',
  cols INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cols from left',
  cat_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Root category ID#',
  create_pages TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Allow to create pages',
  create_materials TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Allow to create materials',
  catalog_offset INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Catalog offset',

  PRIMARY KEY (id),
  KEY (mtype),
  KEY (ufid),
  KEY (interface_id),
  KEY (cat_id),
  INDEX (urn)
) COMMENT 'Price loaders';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_priceloaders_columns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Price loader ID#',
  fid VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field ID#',
  callback TEXT NULL DEFAULT NULL COMMENT 'Callback code',
  callback_download TEXT NULL DEFAULT NULL COMMENT 'Download callback code',
  priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Priority',

  PRIMARY KEY (id),
  KEY (pid),
  KEY (fid),
  INDEX (priority)
) COMMENT 'Price loaders columns';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_imageloaders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  ufid VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Unique field ID#',
  ifid VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Image field ID#',
  name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name',
  urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN',
  sep_string VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Separator string',
  interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Interface ID#',

  PRIMARY KEY (id),
  KEY (mtype),
  KEY (ufid),
  KEY (ifid),
  KEY (interface_id),
  INDEX (urn)
) COMMENT 'Image loaders';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_cart (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID#',
  cart_type int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Cart type ID#',
  epay_interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'E-pay interface ID#',
  epay_login VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay login',
  epay_pass1 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass1',
  epay_pass2 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass2',
  epay_test TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'E-pay test mode',
  epay_currency VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Currency',

  PRIMARY KEY (id),
  KEY cart_type (cart_type),
  KEY (epay_interface_id)
) COMMENT='Cart blocks';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID#',
  shop_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Shop name',
  company VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Company name',
  agency VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Support company name',
  email VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Support e-mail',
  cpa TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'YM purchase',
  default_currency VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Default currency',
  local_delivery_cost DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Local delivery cost',

  PRIMARY KEY (id)
) COMMENT='Yandex Market blocks';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_pages_assoc (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  page_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Page ID#',

  PRIMARY KEY (id, page_id),
  KEY (id),
  KEY (page_id)
) COMMENT='YM blocks to pages association';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_material_types_assoc (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  type VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'YM type',
  param_exceptions TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Params from all fields except...',
  params_callback VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Params callback',

  PRIMARY KEY (id, mtype),
  KEY (id),
  KEY (mtype)
) COMMENT='YM blocks to material types association';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_currencies (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  currency_name VARCHAR(8) NOT NULL DEFAULT '' COMMENT 'Currency ID#',
  currency_rate VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Currency rate',
  currency_plus DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Currency plus, %%',

  PRIMARY KEY (id, currency_name),
  INDEX(currency_name)
) COMMENT='Currencies';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_fields (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  field_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field name',
  field_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field ID#',
  field_callback VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field callback',
  field_static_value VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field static value',

  PRIMARY KEY (id, mtype, field_name),
  KEY (id),
  KEY (mtype),
  KEY (field_id),
  INDEX (field_name)
) COMMENT='YM blocks to material types fields';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_params (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  param_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Param name',
  field_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field ID#',
  field_callback VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field callback',
  param_unit VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Param unit',
  param_static_value VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Param static value',

  PRIMARY KEY (id, mtype, param_name),
  KEY (id),
  KEY (mtype),
  KEY (field_id),
  INDEX (param_name)
) COMMENT='YM blocks to material types params';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_blocks_yml_ignored_fields (
  id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Block ID#',
  mtype INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material type ID#',
  field_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Field ID#',
  
  PRIMARY KEY (id, mtype, field_id),
  KEY (id),
  KEY (mtype),
  KEY (field_id)
) COMMENT='YM blocks to material types params ignored fields';


CREATE TABLE IF NOT EXISTS {$DBPREFIX$}{$PACKAGENAME$}_{$MODULENAME$}_carts (
    cart_type_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cart type ID#',
    uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'User ID#',
    material_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Material ID#',
    meta VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Meta',
    amount INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Amount',

    PRIMARY KEY (cart_type_id, uid, material_id, meta),
    KEY (cart_type_id),
    KEY (uid),
    KEY (material_id),
    KEY (meta)
) COMMENT 'Cart sessions';