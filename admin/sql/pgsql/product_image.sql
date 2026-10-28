-- product_image

	-- get all product_image

	CREATE PROCEDURE getAll(
		IN product_id INT,
		IN product_image_id ARRAY,
		IN cursor INT, -- current cursor (product_image_id) for cursor pagination

		OUT fetch_all
	)
	BEGIN

		SELECT *
			FROM product_image
		WHERE 1 = 1

		@IF !empty(:product_id)
		THEN
			AND product_id = :product_id
		END @IF

		@IF !empty(:product_image_id)
		THEN
			AND product_image_id IN (:product_image_id)
		END @IF

		-- cursor pagination

		@IF isset(:cursor)

		THEN

		    AND product_image.product_image_id > :cursor

		END @IF

		ORDER BY sort_order ASC

	END

	-- get one product_image

	CREATE PROCEDURE get(
		IN product_image_id INT,
		OUT fetch_row
	)
	BEGIN

		SELECT *
			FROM product_image
		WHERE product_image_id = :product_image_id LIMIT 1

	END

	CREATE PROCEDURE add(
		IN product_image ROW,
		OUT fetch_one
	)
	BEGIN

		@FILTER(:product_image, product_image)

		INSERT INTO product_image
			( @KEYS(:product_image) )
		VALUES ( :product_image )
		RETURNING product_image_id;

	END

	CREATE PROCEDURE edit(
		IN product_image ROW,
		IN product_image_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_image, product_image)

		UPDATE product_image
			SET @LIST(:product_image)
		WHERE product_image_id = :product_image_id

	END

	CREATE PROCEDURE delete(
		IN product_id INT,
		IN product_image_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_image
		WHERE 1 = 1

		@IF !empty(:product_image_id)
		THEN
			AND product_image_id IN (:product_image_id)
		END @IF

		@IF !empty(:product_id)
		THEN
			AND product_id = :product_id
		END @IF

	END
