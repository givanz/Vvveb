-- Return status

	-- get all return status

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (return_status_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- return_status
		SELECT *
			FROM return_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND return_status.return_status_id > :cursor
		
		END @IF
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(return_status.return_status_id, return_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get return_status

	CREATE PROCEDURE get(
		IN return_status_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- return_status
		SELECT *
			FROM return_status as _ WHERE return_status_id = :return_status_id;
	END
	
	-- add return_status

	CREATE PROCEDURE add(
		IN return_status ROW,
		IN language_id INT,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:return_status_data  = @FILTER(:return_status, return_status)
		
		
		INSERT INTO return_status 
			
			( @KEYS(:return_status_data), language_id )
			
	  	VALUES ( :return_status_data, :language_id ) RETURNING return_status_id;

	END
	
	-- edit return status

	CREATE PROCEDURE edit(
		IN return_status ROW,
		IN return_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:return_status, return_status)

		UPDATE return_status 
			
			SET @LIST(:return_status) 
			
		WHERE return_status_id = :return_status_id


	END
	
	-- delete return_status

	CREATE PROCEDURE delete(
		IN return_status_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- return_status
		DELETE FROM return_status WHERE return_status_id IN (:return_status_id);
	END
