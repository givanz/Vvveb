-- Currencies

	-- get all order status

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- order_status
		SELECT *
			FROM order_status AS order_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(order_status.order_status_id, order_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get order_status

	PROCEDURE get(
		IN order_status_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- order_status
		SELECT *
			FROM order_status as _ WHERE order_status_id = :order_status_id;
	END
	
	-- add order_status

	PROCEDURE add(
		IN order_status ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:order_status_data  = @FILTER(:order_status, order_status);
		
		
		INSERT INTO order_status 
			
			( @KEYS(:order_status_data), language_id )
			
	  	VALUES ( :order_status_data, :language_id );

	END
	
	-- edit order_status
	CREATE PROCEDURE edit(
		IN order_status ARRAY,
		IN order_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:order_status, order_status);

		UPDATE order_status 
			
			SET @LIST(:order_status) 
			
		WHERE order_status_id = :order_status_id


	END
	
	-- delete order_status

	PROCEDURE delete(
		IN order_status_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- order_status
		DELETE FROM order_status WHERE order_status_id IN (:order_status_id);
	END