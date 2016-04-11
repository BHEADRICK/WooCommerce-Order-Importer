# WooCommerce-Order-Importer

  Merges orders and customers from a separate/old WooCommerce install

  This plugin will check your exported data against existing orders (using the order_key meta) to ensure that no orders are duplicated.
  It will also lookup existing customers by email address and create new customer records as needed, using the new user_id for the customer_user meta value.

# Requirements

Prior to installing or activating this plugin, you must first export the data you want to import.

The following is the sql to use to create and populate the temp tables for your data:
(feel free to add an extra where clause if you only want orders after a certain orderid or date, just be consistent with all of the queries)

    create TABLE temp_orders as select * from wp_posts where post_type = 'shop_order';

    create table temp_postmeta as select pm.* from wp_postmeta pm join wp_posts p on p.ID = pm.post_id where post_type = 'shop_order';

    create table temp_orderitems as select oi.* from wp_woocommerce_order_items oi join wp_posts p on p.ID = oi.order_id where post_type = 'shop_order';

    create table temp_orderitemmeta as select oim.* from wp_woocommerce_order_itemmeta oim
    -- this next line is only necessary if you want to specify a range or orders by id or date
    join wp_woocommerce_order_items oi on oi.order_item_id = oim.order_item_id join wp_posts p on p.ID = oi.order_id where post_type = 'shop_order';

    create table temp_users as select u.* from wp_users u join wp_postmeta pm on  pm.meta_key = '_customer_user' and pm.meta_value = u.ID

    create table temp_usermeta as select um.* from wp_usermeta um join wp_users u on u.ID = um.user_id join wp_postmeta pm on  pm.meta_key = '_customer_user' and pm.meta_value = u.ID


Now export all these tables and import them to the database where you want to import these orders and activate the plugin (the import runs upon plugin activation.) Once the import is complete, you can drop the temp tables and deactivate/delete this plugin from your site. 
