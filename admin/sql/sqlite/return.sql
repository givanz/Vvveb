-- Returns

	-- get all returns

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (return_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- return
		SELECT return.*, return_resolution.name as return_resolution, return_reason.name as return_reason, return_status.name as return_status
			FROM return
			INNER JOIN return_resolution ON return_resolution.return_resolution_id = return.return_resolution_id AND return_resolution.language_id = :language_id
			INNER JOIN return_reason ON return_reason.return_reason_id = return.return_reason_id AND return_reason.language_id = :language_id
			INNER JOIN return_status ON return_status.return_status_id = return.return_status_id AND return_status.language_id = :language_id
		
		WHERE 1 = 1
			
		-- cursor pagination
			
		@IF isset(:cursor)
			
		THEN
			
			AND `return`.return_id > :cursor
			
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
			
			@SQL_COUNT(return.return_id, return) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get return

	CREATE PROCEDURE get(
		IN return_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- return
		SELECT *
			FROM return as _ WHERE return_id = :return_id;
	END
	
	-- add return

	CREATE PROCEDURE add(
		IN return ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:return_data  = @FILTER(:return, return)
		
		
		INSERT INTO return 
			
			( @KEYS(:return_data) )
			
	  	VALUES ( :return_data );

	END
	
	-- edit return
	CREATE PROCEDURE edit(
		IN return ROW,
		IN return_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:return, return)

		UPDATE return
			
			SET @LIST(:return) 
			
		WHERE return_id = :return_id


	END
	
