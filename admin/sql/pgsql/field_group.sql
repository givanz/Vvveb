-- Field group

	-- get all classes

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- field_group
		SELECT *
			FROM field_group AS field_group
		INNER JOIN field_group_content	ON field_group_content.field_group_id = field_group.field_group_id
		WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND field_group_content.language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(field_group.field_group_id, field_group) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get length class

	PROCEDURE get(
		IN field_group_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- field_group
		SELECT *
			FROM field_group as _ 
		INNER JOIN field_group_content	ON field_group_content.field_group_id = _.field_group_id
		WHERE _.field_group_id = :field_group_id

		@IF !empty(:language_id) 
		THEN			
			AND field_group_content.language_id = :language_id
		END @IF
		
		;
	END
	
	-- add length class

	PROCEDURE add(
		IN field_group ARRAY,
		IN language_id INT,
		OUT insert_id
		OUT affected_rows
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:field_group_data  = @FILTER(:field_group, field_group);
		
		INSERT INTO field_group 
			
			( @KEYS(:field_group_data) )
			
	  	VALUES ( :field_group_data);

		-- allow only table fields and set defaults for missing values
		:field_group_content_data  = @FILTER(:field_group, field_group_content);
		
		INSERT INTO field_group_content 
			
			( @KEYS(:field_group_content_data), language_id, field_group_id )
			
	  	VALUES ( :field_group_content_data, :language_id, @result.field_group);

	END
	
	-- edit length class
	CREATE PROCEDURE edit(
		IN field_group ARRAY,
		IN field_group_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:field_group_data  = @FILTER(:field_group, field_group);

		UPDATE field_group 
			
			SET @LIST(:field_group_data) 
			
		WHERE field_group_id = :field_group_id;
		
		-- allow only table fields and set defaults for missing values
		:field_group_content_data  = @FILTER(:field_group, field_group_content);

		UPDATE field_group_content 
			
			SET @LIST(:field_group_content_data) 
			
		WHERE field_group_id = :field_group_id AND language_id = :language_id;


	END

	-- delete length class

	PROCEDURE delete(
		IN field_group_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- field_group
		DELETE FROM field_group_content WHERE field_group_id IN (:field_group_id);
		-- field_group_content
		DELETE FROM field_group WHERE field_group_id IN (:field_group_id);
	END
