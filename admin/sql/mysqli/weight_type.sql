-- Currencies

	-- get all weight types

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- weight_type
		SELECT *
			FROM weight_type AS weight_type
			INNER JOIN weight_type_content ON weight_type_content.weight_type_id = weight_type.weight_type_id
		WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND weight_type_content.language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(weight_type.weight_type_id, weight_type) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get weight type

	PROCEDURE get(
		IN weight_type_id INT,
		IN language_id INT,
		OUT fetch_row, 
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

	PROCEDURE add(
		IN weight_type ARRAY,
		IN language_id INT,
		OUT insert_id
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:weight_type_data  = @FILTER(:weight_type, weight_type);
		
		INSERT INTO weight_type 
			
			( @KEYS(:weight_type_data) )
			
	  	VALUES ( :weight_type_data);

		-- allow only table fields and set defaults for missing values
		:weight_type_content_data  = @FILTER(:weight_type, weight_type_content);
		
		INSERT INTO weight_type_content 
			
			( @KEYS(:weight_type_content_data), language_id, weight_type_id )
			
	  	VALUES ( :weight_type_content_data, :language_id, @result.weight_type);

	END
	
	-- edit weight_type
	CREATE PROCEDURE edit(
		IN weight_type ARRAY,
		IN weight_type_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:weight_type_data  = @FILTER(:weight_type, weight_type);

		UPDATE weight_type 
			
			SET @LIST(:weight_type_data) 
			
		WHERE weight_type_id = :weight_type_id;
		
		-- allow only table fields and set defaults for missing values
		:weight_type_content_data  = @FILTER(:weight_type, weight_type_content);

		UPDATE weight_type_content 
			
			SET @LIST(:weight_type_content_data) 
			
		WHERE weight_type_id = :weight_type_id AND language_id = :language_id;


	END
	
	-- delete weight_type

	PROCEDURE delete(
		IN weight_type_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- weight_type
		DELETE FROM weight_type_content WHERE weight_type_id IN (:weight_type_id);
		-- weight_type_content
		DELETE FROM weight_type WHERE weight_type_id IN (:weight_type_id);
	END
