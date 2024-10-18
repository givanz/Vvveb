-- Shipping statuses

	-- get all shipping statuses

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN

		SELECT *
			FROM shipping_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(shipping_status.shipping_status_id, shipping_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get shipping_status

	PROCEDURE get(
		IN shipping_status_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- shipping_status
		SELECT *
			FROM shipping_status as _ WHERE shipping_status_id = :shipping_status_id;
	END
	
	-- add shipping_status

	PROCEDURE add(
		IN shipping_status ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:shipping_status_data  = @FILTER(:shipping_status, shipping_status)
		
		
		INSERT INTO shipping_status 
			
			( @KEYS(:shipping_status_data), language_id )
			
	  	VALUES ( :shipping_status_data, :language_id );

	END
	
	-- edit shipping_status
	CREATE PROCEDURE edit(
		IN shipping_status ARRAY,
		IN shipping_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:shipping_status, shipping_status)

		UPDATE shipping_status 
			
			SET @LIST(:shipping_status) 
			
		WHERE shipping_status_id = :shipping_status_id


	END
	
	-- delete shipping_status

	PROCEDURE delete(
		IN shipping_status_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- shipping_status
		DELETE FROM shipping_status WHERE shipping_status_id IN (:shipping_status_id);
	END
