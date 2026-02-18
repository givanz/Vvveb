-- Fields

	-- get all fields

	PROCEDURE getAll(
		IN language_id INT,
		IN field_group_id ARRAY,
		IN post_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- field
		SELECT *, field.field_id, field.field_id as array_key
			FROM field
			
			@IF isset(:post_id)
			THEN 
				LEFT JOIN post_field_value ON (field.field_id = post_field_value.field_id AND post_field_value.post_id = :post_id AND post_field_value.language_id = :language_id)
			END @IF	

		WHERE 1 = 1
			
		@IF !empty(:field_group_id) && is_array(:field_group_id)
		THEN			
			AND field.field_group_id IN (:field_group_id)
		END @IF
		
		ORDER BY field.row, field.sort_order
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(field.field_id, field) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get field

	PROCEDURE get(
		IN field_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- field
		SELECT *
			FROM field as _ 
			INNER JOIN field_content	ON field_content.field_id = _.field_id
		WHERE _.field_id = :field_id

		@IF !empty(:language_id) 
		THEN			
			AND field_content.language_id = :language_id
		END @IF
		
		;
	END
	
	-- add field

	PROCEDURE add(
		IN field ARRAY,
		IN language_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:field_data  = @FILTER(:field, field)
		
		INSERT INTO field 
			
			( @KEYS(:field_data) )
			
	  	VALUES ( :field_data);

		-- allow only table fields and set defaults for missing values
		:field_content_data  = @FILTER(:field, field_content)
		
		INSERT INTO field_content 
			
			( @KEYS(:field_content_data), language_id, field_id )
			
	  	VALUES ( :field_content_data, :language_id, @result.field);

	END
	
	-- edit field
	CREATE PROCEDURE edit(
		IN field ARRAY,
		IN field_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:field_data  = @FILTER(:field, field)

		UPDATE field 
			
			SET @LIST(:field_data) 
			
		WHERE field_id = :field_id;
		
		-- allow only table fields and set defaults for missing values
		:field_content_data  = @FILTER(:field, field_content)

		UPDATE field_content 
			
			SET @LIST(:field_content_data) 
			
		WHERE field_id = :field_id AND language_id = :language_id;


	END
	
	-- delete field

	PROCEDURE delete(
		IN field_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- field
		DELETE FROM field_content WHERE field_id IN (:field_id);
		-- field_content
		DELETE FROM field WHERE field_id IN (:field_id);
	END
