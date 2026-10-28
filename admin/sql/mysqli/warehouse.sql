-- Warehouses

	-- get all warehouses

	CREATE PROCEDURE getAll(
		IN status INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (warehouse_id) for cursor pagination
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		-- warehouse
		SELECT *
			FROM warehouse WHERE 1 = 1


		@IF isset(:status) AND :status != ""
		THEN
			AND warehouse.status = :status
		END @IF


		-- cursor pagination
		@IF isset(:cursor)
		THEN
			AND warehouse.warehouse_id > :cursor
		END @IF


		ORDER BY warehouse.sort_order, warehouse.warehouse_id

		LIMIT :limit OFFSET :start;

		SELECT count(*) FROM (

			@SQL_COUNT(warehouse.warehouse_id, warehouse) -- this takes previous query removes limit and replaces select columns with parameter product_id

		) as count;

	END

	-- get warehouse

	CREATE PROCEDURE get(
		IN warehouse_id INT,
		IN status INT,
		OUT fetch_row
	)
	BEGIN
		-- warehouse
		SELECT *
			FROM warehouse as _
		WHERE 1 = 1

		@IF !empty(:warehouse_id)
		THEN
			AND warehouse_id = :warehouse_id
		END @IF

		@IF isset(:status) AND :status != ""
		THEN
			AND status = :status
		END @IF
		;

	END

	-- add warehouse

	CREATE PROCEDURE add(
		IN warehouse ROW,
		OUT insert_id
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:warehouse, warehouse)

		INSERT INTO warehouse

			( @KEYS(:warehouse) )

	  	VALUES ( :warehouse );

	END

	-- edit warehouse

	CREATE PROCEDURE edit(
		IN warehouse ROW,
		IN warehouse_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:warehouse, warehouse)

		@IF !empty(:warehouse)
		THEN
		UPDATE warehouse

			SET @LIST(:warehouse)

		WHERE warehouse_id = :warehouse_id
		END @IF


	END


	-- delete warehouse

	CREATE PROCEDURE delete(
		IN warehouse_id ARRAY,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM warehouse WHERE warehouse_id IN (:warehouse_id);

		DELETE FROM product_warehouse WHERE warehouse_id IN (:warehouse_id);

	END

	-- get warehouse products

	CREATE PROCEDURE getProducts(
		IN warehouse_id INT,
		IN language_id INT,
		OUT fetch_all
	)
	BEGIN
		-- product
		SELECT product.product_id, product_content.name
			FROM product_warehouse
			LEFT JOIN product ON product.product_id = product_warehouse.product_id
			LEFT JOIN product_content ON product_content.product_id = product.product_id
				AND product_content.language_id = :language_id
		WHERE product_warehouse.warehouse_id = :warehouse_id;

	END

	-- set warehouse products

	CREATE PROCEDURE setProducts(
		IN warehouse_product ARRAY,
		IN warehouse_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_warehouse WHERE warehouse_id = :warehouse_id;

		@EACH(:warehouse_product)
			INSERT INTO product_warehouse

				( product_id, warehouse_id)

			VALUES ( :each, :warehouse_id );

	END
