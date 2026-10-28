-- Product warehouse assignments

	-- get all assignments

	CREATE PROCEDURE getAll(
		IN product_id INT,
		IN warehouse_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		-- product_warehouse
		SELECT *
			FROM product_warehouse WHERE 1 = 1

		@IF !empty(:product_id)
		THEN
			AND product_warehouse.product_id = :product_id
		END @IF

		@IF !empty(:warehouse_id)
		THEN
			AND product_warehouse.warehouse_id = :warehouse_id
		END @IF

		LIMIT :limit OFFSET :start;

		SELECT count(*) FROM (

			@SQL_COUNT(product_warehouse.product_id, product_warehouse)

		) as count;

	END

	-- get product warehouse with origin address

	CREATE PROCEDURE getByProduct(
		IN product_id INT,
		OUT fetch_row
	)
	BEGIN
		-- product_warehouse
		SELECT product_warehouse.*, warehouse.name, warehouse.country_id, warehouse.region_id,
			warehouse.city, warehouse.postal_code, warehouse.address_1
			FROM product_warehouse
			INNER JOIN warehouse ON warehouse.warehouse_id = product_warehouse.warehouse_id
				AND warehouse.status = 1
		WHERE product_warehouse.product_id = :product_id
		ORDER BY warehouse.sort_order, warehouse.warehouse_id
		LIMIT 1;

	END

	-- add assignment

	CREATE PROCEDURE add(
		IN product_warehouse ROW,
		OUT insert_id
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_warehouse, product_warehouse)

		INSERT INTO product_warehouse

			( @KEYS(:product_warehouse) )

	  	VALUES ( :product_warehouse );

	END

	-- delete assignments

	CREATE PROCEDURE delete(
		IN product_id ARRAY,
		IN warehouse_id INT,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_warehouse WHERE product_id IN (:product_id)

		@IF !empty(:warehouse_id)
		THEN
			AND warehouse_id = :warehouse_id
		END @IF
		;

	END
