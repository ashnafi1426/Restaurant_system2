SELECT COUNT(*) as total_reviews FROM menu_item_reviews;
SELECT id, guest_id, menu_item_id, rating, status, created_at FROM menu_item_reviews ORDER BY created_at DESC LIMIT 10;
SELECT * FROM menu_item_reviews LIMIT 1;
