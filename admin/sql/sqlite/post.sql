-- Posts

	-- get all post 

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN search CHAR,
		IN like CHAR,
		IN username CHAR,
		IN status CHAR,
		IN taxonomy_item_slug CHAR,
		IN post_id ARRAY,
		IN taxonomy_item_id INT,
		-- global	
		IN type CHAR,
		IN site_id INT,
		IN admin_id INT,
		IN language_id INT,
		-- comment
		IN comment_count INT,
		IN comment_status INT,
		-- archive
		IN year INT,
		IN month INT,
		-- taxonomy
		IN categories INT,
		IN tags INT,
		IN taxonomy CHAR,
		
		IN order_by CHAR,
		IN direction CHAR,

		-- return array of posts for posts query
		OUT fetch_all,
		-- return posts count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT pd.*,post.*,ad.username,ad.display_name,ad.admin_id,ad.email, ad.avatar, ad.bio, ad.first_name, ad.last_name
			@IF isset(:comment_count)
			THEN
				,(SELECT COUNT(c.comment_id) 
						FROM comment c 
					WHERE 
						post.post_id = c.post_id
					
						@IF isset(:comment_status)
						THEN
							AND c.status = :comment_status
						END @IF
					) 
				
				AS comment_count
			END @IF
			
					
		FROM post
			LEFT JOIN post_content pd ON (
				post.post_id = pd.post_id 
				
				@IF isset(:language_id)
				THEN
					AND pd.language_id = :language_id
				END @IF

			)  
			LEFT JOIN post_to_site ps ON (post.post_id = ps.post_id)  
			LEFT JOIN admin ad ON (post.admin_id = ad.admin_id)  
			
			@IF isset(:taxonomy_item_id) || isset(:taxonomy_item_slug)
			THEN
				LEFT JOIN post_to_taxonomy_item pt ON (post.post_id = pt.post_id)   
			END @IF			
		
			@IF isset(:search)
			THEN 
				JOIN post_content_search pcs ON (pcs.ROWID = pd.ROWID)   
			END @IF	
		WHERE 1 = 1 
		
			@IF isset(:type) && !empty(:type)
			THEN
				AND post.type = :type
			END @IF			
			
			@IF isset(:status) && !empty(:status)
			THEN
				AND post.status = :status
			@ELSE
				AND post.status = 'publish'
			END @IF
			
			-- username/author
			@IF isset(:username)
			THEN
				AND post.admin_id = (SELECT admin_id FROM admin WHERE username = :username LIMIT 1)
			END @IF	
			
			-- admin_id
			@IF isset(:admin_id)
			THEN
				AND post.admin_id = :admin_id
			END @IF

            -- search
            @IF isset(:search) && !empty(:search)
			THEN 
				-- AND pd.name LIKE CONCAT('%',:search,'%')
				-- AND MATCH(pd.name, pd.content) AGAINST(:search)
				-- AND (pcs.name MATCH :search OR pcs.content MATCH :search) 
				AND (post_content_search = :search) 
        	END @IF	     
            
			-- like
			@IF isset(:like) && !empty(:like)
			THEN 
				AND pd.name LIKE '%' || :like || '%'
			END @IF  

            -- post_id
			@IF isset(:post_id) && count(:post_id) > 0
			THEN 
			
				AND post.post_id IN (:post_id)
				
			END @IF		

			@IF isset(:site_id)
			THEN
				AND ps.site_id = :site_id
			END @IF

			
			@IF isset(:taxonomy_item_id)
			THEN
				AND pt.taxonomy_item_id IN (:taxonomy_item_id)
			END @IF	
			
			
			@IF isset(:taxonomy_item_slug)
			THEN
				AND pt.taxonomy_item_id IN (
					SELECT ti.taxonomy_item_id 
						FROM taxonomy_item_content AS tic
						  -- include child categories
						LEFT JOIN taxonomy_item AS ti ON ti.parent_id = tic.taxonomy_item_id OR ti.taxonomy_item_id  = tic.taxonomy_item_id
					WHERE slug = :taxonomy_item_slug
				)
			END @IF

			-- month
			@IF isset(:month) && !empty(:month)
			THEN
				AND strftime('%M', post.created_at) = :month
			END @IF					

			-- year
			@IF isset(:year) && !empty(:year)
			THEN
				AND strftime('%Y', post.created_at) = :year
			END @IF					

			-- ORDER BY parameters can't be binded, because they are added to the query directly they must be properly sanitized by only allowing a predefined set of values
			@IF isset(:order_by)
			THEN
				ORDER BY post.$order_by $direction		
			@ELSE
				ORDER BY post.post_id DESC
			END @IF		
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;


		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(post.post_id, post) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;
	 
	END
	

	-- get one post

	CREATE PROCEDURE get(
		IN post_id INT,
		IN slug CHAR,
		IN language_id INT,
		IN comment_count INT,
		IN comment_status INT,
		IN admin_id INT,
		IN type CHAR,
		
		OUT fetch_row, -- post
		OUT fetch_all, -- content
		OUT fetch_all, -- meta
		OUT fetch_all, -- post_to_site
		OUT fetch_all, -- post_to_taxonomy_item
	)
	BEGIN

		SELECT _.*,pd.*,_.post_id,ad.admin_id,ad.username,ad.display_name,ad.email
		
		@IF isset(:comment_count)
			THEN
				,(SELECT COUNT(c.comment_id) 
						FROM comment c 
					WHERE 
						_.post_id = c.post_id
					
						@IF isset(:comment_status)
						THEN
							AND c.status = :comment_status
						END @IF
					) 
				
				AS comment_count
			END @IF 
			
			FROM post AS _
			LEFT JOIN post_content pd ON 
				(
					_.post_id = pd.post_id 

					@IF isset(:language_id)
					THEN
						AND pd.language_id = :language_id
					END @IF
				)  

			LEFT JOIN admin ad ON (_.admin_id = ad.admin_id)  				
			
		WHERE 1 = 1

            @IF isset(:slug) && !(isset(:post_id) && :post_id) 
			THEN 
				AND pd.slug = :slug 
        	END @IF			

            @IF isset(:post_id) && :post_id > 0
			THEN
                AND _.post_id = :post_id
        	END @IF			
			
            @IF isset(:admin_id)
			THEN
                AND _.admin_id = :admin_id
        	END @IF			
			
			@IF isset(:type)
			THEN
                AND _.type = :type
        	END @IF			

        LIMIT 1; 
		
		-- content
		SELECT *, language_id as array_key -- (underscore) _ column means that this column (language_id) value will be used as array key when adding row to result array
			FROM post_content 
		WHERE post_id = @result.post_id;
		
	 
		-- meta
		SELECT `key` as array_key,`value` as array_value FROM post_meta as _
			WHERE _.post_id = @result.post_id;	 
	 
		-- post_to_site
		SELECT site_id as array_key, site_id FROM post_to_site
			WHERE post_to_site.post_id = @result.post_id;	 
	 
		-- post_to_taxonomy_item
		SELECT taxonomy_item_id as array_key, taxonomy_item_id FROM post_to_taxonomy_item
			WHERE post_to_taxonomy_item.post_id = @result.post_id;	 
	 
	END

	-- Add new post

	CREATE PROCEDURE add(
		IN post ARRAY,
		IN post_content ARRAY,
		IN taxonomy_item_id ARRAY,
		IN site_id ARRAY,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:post_data  = @FILTER(:post, post)
		
		
		INSERT INTO post 
			
			( @KEYS(:post_data) )
			
	  	VALUES ( :post_data );

		:post_content  = @FILTER(:post_content, post_content, false, true)


		@EACH(:post_content) 
			INSERT INTO post_content 
		
				( @KEYS(:each), post_id)
			
			VALUES ( :each, @result.post);

		@EACH(:taxonomy_item_id) 
			INSERT INTO post_to_taxonomy_item 
		
				( `taxonomy_item_id`, post_id)
			
			VALUES ( :each, @result.post)
			ON CONFLICT(`post_id`,`taxonomy_item_id`) DO UPDATE SET `taxonomy_item_id` = :each;

		@EACH(:site_id) 
		INSERT INTO post_to_site 
		
			( `post_id`, `site_id` )
			
			VALUES ( @result.post, :each );		

	END

	-- Edit post

	CREATE PROCEDURE edit(
		IN post ARRAY,
		IN post_content ARRAY,
		IN taxonomy_item_id ARRAY,
		IN post_id INT,
		IN site_id ARRAY,
		OUT insert_id,
		OUT affected_rows,
		OUT insert_id,
		OUT affected_rows,
		OUT insert_id,
		OUT affected_rows
	)
	BEGIN
	
		:post_content  = @FILTER(:post_content, post_content, false, true)
		
		@EACH(:post_content) 
			INSERT INTO post_content 
		
				( @KEYS(:each), post_id)
			
			VALUES ( :each, :post_id)

			ON CONFLICT(post_id, language_id) DO UPDATE SET @LIST(:each);


		@IF isset(:taxonomy_item_id)
		THEN
			DELETE FROM post_to_taxonomy_item WHERE post_id = :post_id
		END @IF;

		@EACH(:taxonomy_item_id) 
				INSERT INTO post_to_taxonomy_item 
			
					(taxonomy_item_id, post_id)
				
				VALUES ( :each, :post_id)
				ON CONFLICT(`post_id`,`taxonomy_item_id`) DO UPDATE SET `taxonomy_item_id` = :each;

		@IF isset(:site_id) 
		THEN
			DELETE FROM post_to_site WHERE post_id = :post_id
		END @IF;

		@EACH(:site_id)
		INSERT INTO post_to_site 
		
			( `post_id`, `site_id` )
			
		VALUES ( :post_id, :each );			


		-- allow only table fields and set defaults for missing values
		@FILTER(:post, post)
	
		@IF !empty(:post) 
		THEN
			UPDATE post 
				
				SET @LIST(:post) 
				
			WHERE post_id = :post_id
		END @IF;


	END
	
	-- Edit post content

	CREATE PROCEDURE editContent(
		IN post_content ARRAY,
		IN post_id INT,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN
	
		:post_content  = @FILTER(:post_content, post_content)
	
		UPDATE post_content 
			
			SET @LIST(:post_content) 
			
		WHERE post_id = :post_id AND language_id = :language_id
	END
	

	-- Delete post

	CREATE PROCEDURE delete(
		IN  post_id ARRAY,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
		
		DELETE FROM post_to_taxonomy_item WHERE post_id IN (:post_id);
		DELETE FROM post_to_site WHERE post_id IN (:post_id);
		DELETE FROM post_content WHERE post_id IN (:post_id);
		DELETE FROM post WHERE post_id IN (:post_id);
	 
	END
	
	
	
	-- Get tags

	CREATE PROCEDURE postTags(
		IN  post_id INT,
		OUT affected_rows
	)
	BEGIN
	END
	
	
	-- Get categories

	CREATE PROCEDURE postCategories(
		IN  post_id INT,
		OUT affected_rows
	)
	BEGIN
	END
	
	-- Add categories
	CREATE PROCEDURE setPostTaxonomy(
		IN  post_id INT,
		IN  taxonomy_item ARRAY,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM post_to_taxonomy_item WHERE post_id = :post_id;
	
		@EACH(:taxonomy_item) 
			INSERT INTO post_to_taxonomy_item 
		
				( `post_id`, `taxonomy_item_id`)
			
			VALUES ( :post_id, :each);
	END


	-- Get archives

	CREATE PROCEDURE getArchives(
		IN start INT,
		IN limit INT,
		IN interval CHAR,
		IN type CHAR,
	)
	BEGIN
	
		SELECT 
			strftime('%Y',archives.created_at) AS `year` 
			
			@IF isset(:interval) AND (:interval == "month" || :interval == "day")
			THEN
				,strftime('%M',archives.created_at) AS `month` 
			END @IF

			@IF isset(:interval) AND :interval == "day"
			THEN
				,strftime('%D',archives.created_at) AS `day` 
			END @IF
			
			,count(archives.post_id) as count 
			
		FROM post AS archives
		LEFT JOIN post_to_site ps ON (archives.post_id = ps.post_id) 
		
		WHERE 1 = 1 
			
			@IF isset(:type)
			THEN
				AND type = :type
			END @IF
			
			@IF isset(:site_id)
			THEN
				AND ps.site_id = :site_id
			END @IF

		
		GROUP BY 
			strftime('%Y',archives.created_at)
			
			@IF isset(:interval) AND (:interval == "month" || :interval == "day")
			THEN
				,strftime('%M',archives.created_at)
			END @IF

			@IF isset(:interval) AND :interval == "day"
			THEN
				,strftime('%D',archives.created_at)
			END @IF
			
		ORDER BY 			
			strftime('%Y',archives.created_at)
			
			@IF isset(:interval) AND (:interval == "month" || :interval == "day")
			THEN
				,strftime('%M',archives.created_at)
			END @IF

			@IF isset(:interval) AND :interval == "day"
			THEN
				,strftime('%D',archives.created_at)
			END @IF


		-- limit
		@IF isset(:limit) AND :limit > 0
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;		
	
	END
