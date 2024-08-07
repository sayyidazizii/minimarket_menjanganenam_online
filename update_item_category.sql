UPDATE `invt_item` a 
INNER JOIN invt_item_category b ON b.`item_category_name` = a.`category`
SET a.`item_category_id` = b.item_category_id;

UPDATE `invt_item_packge` a 
INNER JOIN invt_item b ON b.`item_id` = a.`item_id`
SET a.`item_category_id` = b.item_category_id;

UPDATE `invt_item_stock` a 
INNER JOIN invt_item b ON b.`item_id` = a.`item_id`
SET a.`item_category_id` = b.item_category_id;