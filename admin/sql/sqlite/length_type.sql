-- Length type

	-- get all length types

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- length_type
		SELECT *
			FROM length_type AS length_type
		INNER JOIN length_type_content	ON length_type_content.length_type_id = length_type.length_type_id
		WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND length_type_content.language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(length_type.length_type_id, length_type) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get length type

	PROCEDURE get(
		IN length_type_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- length_type
		SELECT *
			FROM length_type as _ 
		INNER JOIN length_type_content	ON length_type_content.length_type_id = _.length_type_id
		WHERE _.length_type_id = :length_type_id

		@IF !empty(:language_id) 
		THEN			
			AND length_type_content.language_id = :language_id
		END @IF
		
		;
	END
	
	-- add length type

	PROCEDURE add(
		IN length_type ARRAY,
		IN language_id INT,
		OUT insert_id
		OUT affected_rows
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:length_type_data  = @FILTER(:length_type, length_type);
		
		INSERT INTO length_type 
			
			( @KEYS(:length_type_data) )
			
	  	VALUES ( :length_type_data);

		-- allow only table fields and set defaults for missing values
		:length_type_content_data  = @FILTER(:length_type, length_type_content);
		
		INSERT INTO length_type_content 
			
			( @KEYS(:length_type_content_data), language_id, length_type_id )
			
	  	VALUES ( :length_type_content_data, :language_id, @result.length_type);

	END
	
	-- edit length type
	CREATE PROCEDURE edit(
		IN length_type ARRAY,
		IN length_type_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:length_type_data  = @FILTER(:length_type, length_type);

		UPDATE length_type 
			
			SET @LIST(:length_type_data) 
			
		WHERE length_type_id = :length_type_id;
		
		-- allow only table fields and set defaults for missing values
		:length_type_content_data  = @FILTER(:length_type, length_type_content);

		UPDATE length_type_content 
			
			SET @LIST(:length_type_content_data) 
			
		WHERE length_type_id = :length_type_id AND language_id = :language_id;


	END

	-- delete length type

	PROCEDURE delete(
		IN length_type_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- length_type
		DELETE FROM length_type_content WHERE length_type_id IN (:length_type_id);
		-- length_type_content
		DELETE FROM length_type WHERE length_type_id IN (:length_type_id);
	END
