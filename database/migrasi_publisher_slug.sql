-- Migrasi untuk database optl_pustaka lama: identitas publik publisher.
ALTER TABLE publishers
    ADD COLUMN slug VARCHAR(120) NOT NULL DEFAULT '' AFTER name;

UPDATE publishers SET slug = CASE publisher_id
    WHEN 1 THEN 'gramedia-pustaka-utama'
    WHEN 2 THEN 'bentang-pustaka'
    WHEN 3 THEN 'mizan-publishing'
    WHEN 4 THEN 'erlangga'
    WHEN 5 THEN 'pustaka-digital-press'
    ELSE CONCAT('publisher-', publisher_id)
END;

ALTER TABLE publishers ADD UNIQUE KEY uq_publishers_slug (slug);

CREATE OR REPLACE VIEW v_books_catalog AS
SELECT b.book_id, b.title, b.slug, b.description, b.published_year, b.isbn,
       b.storage_path, b.file_size, b.checksum_sha256, b.page_count, b.cover_path,
       b.is_active, b.created_at,
       a.name AS author_name, a.slug AS author_slug, a.country AS author_country, a.bio AS author_bio,
       g.genre_id, g.name AS genre_name,
       p.name AS publisher_name, p.slug AS publisher_slug, p.city AS publisher_city
FROM books b
JOIN authors a ON a.author_id = b.author_id
JOIN genres g ON g.genre_id = b.genre_id
JOIN publishers p ON p.publisher_id = b.publisher_id;
