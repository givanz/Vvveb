-- Product attributes

	-- get all product attributes

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN product_id ARRAY,
		IN cursor INT, -- current cursor (attribute_id) for cursor pagination
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		-- product_attribute
		SELECT product_attribute.*
			FROM product_attribute

		WHERE 1 = 1

		-- cursor pagination


		@IF isset(:product_id)
		THEN
			AND product_attribute.product_id IN (:product_id)
		END @IF

		@IF isset(:cursor)
		THEN
			AND product_attribute.attribute_id > :cursor
		END @IF

		-- limit
		@IF isset(:limit)
		THEN
		    LIMIT :limit

		    @IF isset(:start)
		    THEN
			OFFSET :start
		    END @IF

		END @IF;

		SELECT count(*) FROM (

			@SQL_COUNT(product_attribute.attribute_id, product_attribute) -- this takes previous query removes limit and replaces select columns with parameter product_id

		) as count;

	END

	-- get product attribute

	CREATE PROCEDURE get(
		IN product_attribute_id INT,
		IN product_id INT,
		IN attribute_id INT,
		IN language_id INT,
		OUT fetch_row
	)
	BEGIN
		-- product_attribute
		SELECT *
			FROM product_attribute as _

		WHERE 1 = 1

		@IF !empty(:product_id)
		THEN
			AND _.product_id = :product_id
		END @IF

		@IF !empty(:attribute_id)
		THEN
			AND _.attribute_id = :attribute_id
		END @IF

		@IF !empty(:language_id)
		THEN
			AND _.language_id = :language_id
		END @IF

		@IF !empty(:product_attribute_id)
		THEN
			AND _.product_attribute_id = :product_attribute_id
		END @IF

		LIMIT 1;
	END

	-- add product attribute

	CREATE PROCEDURE add(
		IN product_attribute ROW,
		OUT insert_id
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:product_attribute_data  = @FILTER(:product_attribute, product_attribute)


		INSERT INTO product_attribute

			( @KEYS(:product_attribute_data) )

	  	VALUES ( :product_attribute_data );

	END

	-- edit product_attribute

	CREATE PROCEDURE edit(
		IN product_attribute ROW,
		IN product_attribute_id INT,
		IN product_id INT,
		IN attribute_id INT,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_attribute, product_attribute)

		UPDATE product_attribute

			SET @LIST(:product_attribute)

		WHERE 1 =1 

		@IF !empty(:product_id)
		THEN
			AND product_id = :product_id
		END @IF

		@IF !empty(:attribute_id)
		THEN
			AND attribute_id = :attribute_id
		END @IF

		@IF !empty(:language_id)
		THEN
			AND language_id = :language_id
		END @IF

		@IF !empty(:product_attribute_id)
		THEN
			AND product_attribute_id = :product_attribute_id
		END @IF


	END


	-- delete product attribute

	CREATE PROCEDURE delete(
		IN product_attribute_id ARRAY,
		IN product_id ARRAY,
		IN attribute_id ARRAY,
		IN language_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		@IF !empty(:product_id)
		THEN
		DELETE FROM product_attribute
		WHERE product_id IN (:product_id)
			AND attribute_id IN (:attribute_id)
			AND language_id IN (:language_id)
		END @IF;	

		@IF !empty(:product_attribute_id)
		THEN
			DELETE FROM product_attribute WHERE product_attribute_id IN (:product_attribute_id)
		END @IF;	
	END
