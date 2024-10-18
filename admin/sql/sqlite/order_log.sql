-- Order log

	-- get all order log

	PROCEDURE getAll(
		IN order_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- order_log
		SELECT *
			FROM order_log WHERE 1 = 1
			
		@IF !empty(:order_id) 
		THEN			
			AND order_id = :order_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(order_log.order_log_id, order_log) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get order log

	PROCEDURE get(
		IN order_log_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- order_log
		SELECT *
			FROM order_log as _ WHERE order_log_id = :order_log_id;
	END
	
	-- add order_log

	PROCEDURE add(
		IN order_log ARRAY,
		IN order_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:order_log_data  = @FILTER(:order_log, order_log)
		
		
		INSERT INTO order_log 
			
			( @KEYS(:order_log_data), order_id )
			
	  	VALUES ( :order_log_data, :order_id );

	END
	
	-- edit order_log
	CREATE PROCEDURE edit(
		IN order_log ARRAY,
		IN order_log_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:order_log, order_log)

		UPDATE order_log 
			
			SET @LIST(:order_log) 
			
		WHERE order_log_id = :order_log_id


	END
	
	-- delete order_log

	PROCEDURE delete(
		IN order_log_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- order_log
		DELETE FROM order_log WHERE order_log_id IN (:order_log_id);
	END
