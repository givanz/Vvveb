-- Languages

	-- get all languages

	PROCEDURE getAll(
		IN status INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		-- count
		OUT fetch_one, 
	)
	BEGIN
		-- language
		SELECT *, code as array_key
			FROM language as language WHERE 1 = 1
			
			@IF isset(:status) 
			THEN
				AND status = :status
			END @IF
			
			ORDER BY status DESC
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;
			
			SELECT count(*) FROM (
					
					@SQL_COUNT(language.language_id, language) -- this takes previous query removes limit and replaces select columns with parameter product_id
					
			) as count;			
	END	
	
	-- get language

	PROCEDURE get(
		IN language_id INT,
		IN code CHAR,
		OUT fetch_row, 
	)
	BEGIN
		-- language
		SELECT *, language_id
			FROM language as _ 
		WHERE 1 = 1 
			
			@IF isset(:language_id) 
			THEN
				AND language_id = :language_id
			END @IF
			
			@IF isset(:code) 
			THEN
				AND code = :code
			END @IF

		LIMIT 1;
	END
	
	-- add language

	PROCEDURE add(
		IN language ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:language_data  = @FILTER(:language, language);
		
		
		INSERT INTO language 
			
			( @KEYS(:language_data) )
			
	  	VALUES ( :language_data );

	END

	-- delete language

	CREATE PROCEDURE delete(
		IN  language_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		
		DELETE FROM language WHERE language_id IN (:language_id);
		
	END

	-- edit language
	
	CREATE PROCEDURE edit(
		IN language ARRAY,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:language, language);
		
		
		UPDATE language 
			
			SET @LIST(:language) 
			
		WHERE language_id = :language_id

	END
