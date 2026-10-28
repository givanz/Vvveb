-- Currencies

	-- get all weight types

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (weight_type_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- weight_type
		SELECT *, weight_type.weight_type_id as array_key
			FROM weight_type
		INNER JOIN weight_type_content ON weight_type_content.weight_type_id = weight_type.weight_type_id
		WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND weight_type_content.language_id = :language_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND weight_type.weight_type_id > :cursor
		
		END @IF
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(weight_type.weight_type_id, weight_type) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get weight type

	CREATE PROCEDURE get(
		IN weight_type_id INT,
		IN language_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- weight_type
		SELECT *
			FROM weight_type as _ 
		INNER JOIN weight_type_content	ON weight_type_content.weight_type_id = _.weight_type_id
		WHERE _.weight_type_id = :weight_type_id

		@IF !empty(:language_id) 
		THEN			
			AND weight_type_content.language_id = :language_id
		END @IF
		
		;
	END
	
	-- add weight_type

	CREATE PROCEDURE add(
		IN weight_type ROW,
		IN language_id INT,
		OUT fetch_one,
		OUT affected_rows
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:weight_type_data  = @FILTER(:weight_type, weight_type)
		
		INSERT INTO weight_type 
			
@IF !empty(:weight_type_data)
		THEN
			
			( @KEYS(:weight_type_data) )
			
		  	VALUES ( :weight_type_data ) RETURNING weight_type_id
			
		@ELSE
			
			() VALUES () RETURNING weight_type_id
			
		END @IF;


		-- allow only table fields and set defaults for missing values
		:weight_type_content_data  = @FILTER(:weight_type, weight_type_content)
		
		INSERT INTO weight_type_content 
			
			( @KEYS(:weight_type_content_data), language_id, weight_type_id )
			
	  	VALUES ( :weight_type_content_data, :language_id, @result.weight_type);

	END
	
	-- edit weight_type
	CREATE PROCEDURE edit(
		IN weight_type ROW,
		IN weight_type_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:weight_type_data  = @FILTER(:weight_type, weight_type)

		@IF !empty(:weight_type_data)
		THEN
			UPDATE weight_type 
				
				SET @LIST(:weight_type_data) 
				
			WHERE weight_type_id = :weight_type_id
		END @IF;
		
		-- allow only table fields and set defaults for missing values
		:weight_type_content_data  = @FILTER(:weight_type, weight_type_content)

		UPDATE weight_type_content 
			
			SET @LIST(:weight_type_content_data) 
			
		WHERE weight_type_id = :weight_type_id AND language_id = :language_id;


	END
	
	-- delete weight_type

	CREATE PROCEDURE delete(
		IN weight_type_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows 
	)
	BEGIN
		-- weight_type
		DELETE FROM weight_type_content WHERE weight_type_id IN (:weight_type_id);
		-- weight_type_content
		DELETE FROM weight_type WHERE weight_type_id IN (:weight_type_id);
	END
