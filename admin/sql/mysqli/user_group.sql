-- User groups

	-- get all user groups

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- user_group
		SELECT user_group.*,user_group_content.name, user_group_content.content, user_group_content.language_id
			FROM user_group AS user_group 
			INNER JOIN user_group_content ON user_group_content.user_group_id = user_group.user_group_id
		WHERE 1 = 1
		
		
		@IF !empty(:language_id) 
		THEN			
			AND user_group_content.language_id = :language_id
		END @IF
				
		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(user_group.user_group_id, user_group) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	

	-- get user group

	PROCEDURE get(
		IN user_group_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- user_group
		SELECT _.*, user_group_content.name, user_group_content.content, user_group_content.language_id
			FROM user_group as _ 
			INNER JOIN user_group_content ON user_group_content.user_group_id = _.user_group_id
		WHERE _.user_group_id = :user_group_id;
	END
	
	-- add user group

	PROCEDURE add(
		IN user_group ARRAY,
		IN language_id INT,
		OUT insert_id
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:user_group_data  = @FILTER(:user_group, user_group);
		
		INSERT INTO user_group 
			
			( @KEYS(:user_group_data) )
			
	  	VALUES ( :user_group_data );
	  	
	  	:user_group_content  = @FILTER(:user_group, user_group_content);
	  	
		INSERT INTO user_group_content 
			
			( @KEYS(:user_group_content), language_id, user_group_id )
			
	  	VALUES ( :user_group_content, :language_id, @result.user_group);

	END
	
	-- edit user group
	
	CREATE PROCEDURE edit(
		IN user_group ARRAY,
		IN user_group_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:user_group_data = @FILTER(:user_group, user_group);

		UPDATE user_group
			
			SET @LIST(:user_group_data) 
			
		WHERE user_group_id = :user_group_id;

		-- allow only table fields and set defaults for missing values
		:user_group_content  = @FILTER(:user_group, user_group_content);

		UPDATE user_group_content
			
			SET @LIST(:user_group_content) 
			
		WHERE user_group_id = :user_group_id;


	END
	
	-- delete user_group

	PROCEDURE delete(
		IN user_group_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- user_group
		DELETE FROM user_group_content WHERE user_group_id IN (:user_group_id);
		DELETE FROM user_group WHERE user_group_id IN (:user_group_id);
	END
