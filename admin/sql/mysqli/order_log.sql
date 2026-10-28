-- Order log

	-- get all order log

	CREATE PROCEDURE getAll(
		IN order_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (order_log_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- order_log
		SELECT *
			FROM order_log WHERE 1 = 1
			
		@IF !empty(:order_id) 
		THEN			
			AND order_id = :order_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND order_log.order_log_id > :cursor
		
		END @IF
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(order_log.order_log_id, order_log) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get order log

	CREATE PROCEDURE get(
		IN order_log_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- order_log
		SELECT *
			FROM order_log as _ WHERE order_log_id = :order_log_id;
	END
	
	-- add order_log

	CREATE PROCEDURE add(
		IN order_log ROW,
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
		IN order_log ROW,
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

	CREATE PROCEDURE delete(
		IN order_log_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- order_log
		DELETE FROM order_log WHERE order_log_id IN (:order_log_id);
	END
