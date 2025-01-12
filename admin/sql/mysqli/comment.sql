-- Comments

	-- get all comment 

	CREATE PROCEDURE getAll(
		-- variables
		IN language_id INT,
		IN site_id INT,
		IN post_id INT,
		IN user_id INT,
		IN status INT,
		IN post_title INT,

		-- pagination
		IN start INT,
		IN limit INT,
		IN order CHAR,

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

		SELECT user.username, user.email, user.first_name,  user.last_name,  user.display_name, user.avatar, user.bio,  user.subscribe, comment.*, comment_id as array_key
			@IF isset(:post_title) AND :post_title
			THEN 
				,post_content.name, post_content.slug
			END @IF			
			
			FROM comment
			LEFT JOIN user ON (user.user_id = comment.user_id)	
			
			@IF isset(:post_title) AND :post_title
			THEN 
				LEFT JOIN post_content ON (post_content.post_id = comment.post_id)
			END @IF
		WHERE 1 = 1
            
            -- post
            @IF isset(:post_id)
			THEN 
				AND comment.post_id  = :post_id
        	END @IF	            
            
			-- post slug
            @IF isset(:slug)
			THEN 
				AND comment.post_id  = (SELECT post_id FROM post_content WHERE slug = :slug LIMIT 1) 
			END @IF

            -- user
            @IF isset(:user_id)
			THEN 
				AND comment.user_id  = :user_id
        	END @IF	            
			
			-- user
            @IF isset(:language_id) AND isset(:post_title)
			THEN 
				AND post_content.language_id  = :language_id
        	END @IF	              
            
			-- user
            @IF isset(:status)
			THEN 
				AND comment.status  = :status
        	END @IF	            

		ORDER BY parent_id, comment_id 
		
		@IF isset(:order) AND :order == "desc"
		THEN 
			DESC
		END @IF	 
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(comment_id, comment) -- this takes previous query removes limit and replaces select columns with parameter comment_id
			
		) as count;	 
	END
	

	-- get one comment

	CREATE PROCEDURE get(
		IN comment_id INT,
		OUT fetch_row,
	)
	BEGIN

		SELECT * 
			FROM comment AS _
			LEFT JOIN user ON user.user_id = _.user_id
		WHERE 1 = 1

            @IF isset(:comment_id)
			THEN
                AND _.comment_id = :comment_id
        	END @IF			

        LIMIT 1; 
		
		
		-- SELECT `key` as array_key,value as array_value FROM comment_meta as _
			-- WHERE _.comment_id = @result.comment_id
		
          
	END

	-- Add new comment

	CREATE PROCEDURE add(
		IN comment ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:comment, comment)
		
		INSERT INTO comment 
			
			( @KEYS(:comment) )
			
	  	VALUES ( :comment )
        
	END

	-- Edit comment

	CREATE PROCEDURE edit(
		IN comment ARRAY,
		IN  id_comment INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:comment, comment)

		UPDATE comment 
			
			SET  @LIST(:comment) 
			
		WHERE comment_id = :comment_id
	 
	END
	
	-- Delete comment

	CREATE PROCEDURE delete(
		IN  comment_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM comment WHERE comment_id IN (:comment_id)
	 
	END
