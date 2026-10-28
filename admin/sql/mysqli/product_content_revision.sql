-- Product revisions

	-- get all product_content_revisions

	CREATE PROCEDURE getAll(
		IN product_id INT,
		IN language_id INT,
		IN created_at INT,
		IN content INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (product_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- product_content_revision
		SELECT product_content_revision.product_id, product_content_revision.language_id, product_content_revision.created_at, product_content_revision.admin_id, admin.display_name, admin.username

			@IF !empty(:content) 
			THEN			
				,content
			END @IF
		
			FROM product_content_revision
			LEFT JOIN admin ON (admin.admin_id = product_content_revision.admin_id)
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN			
			AND product_content_revision.product_id = :product_id
		END @IF		
				
		@IF !empty(:language_id) 
		THEN			
			AND product_content_revision.language_id = :language_id
		END @IF	
					
		@IF !empty(:admin_id) 
		THEN			
			AND product_content_revision.admin_id = :admin_id
		END @IF	
					
		@IF !empty(:created_at) 
		THEN			
			AND product_content_revision.created_at = :created_at
		END @IF				
		
		
		-- cursor pagination
		
		
		@IF isset(:cursor)
		
		
		THEN
		
		
			AND product_content_revision.product_id > :cursor
		
		
		END @IF
		
		
		ORDER BY product_content_revision.created_at DESC
		
		@IF !empty(:limit) 
		THEN			
			LIMIT :limit OFFSET :start
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_content_revision.product_id, product_content_revision) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product content revision

	CREATE PROCEDURE get(
		IN product_id INT,
		IN language_id INT,
		IN admin_id INT,
		IN created_at CHAR,
		OUT fetch_row 
	)
	BEGIN
		-- product_content_revision
		SELECT *
			FROM product_content_revision as _ 
			LEFT JOIN admin ON (admin.admin_id = _.admin_id)
		WHERE 
			1 = 1
	
			@IF !empty(:product_id) 
			THEN			
				AND _.product_id = :product_id
			END @IF			
			
			@IF !empty(:language_id) 
			THEN			
				AND _.language_id = :language_id
			END @IF

			@IF !empty(:admin_id) 
			THEN			
				AND _.admin_id = :admin_id
			END @IF	
		
			@IF !empty(:created_at) 
			THEN			
				AND _.created_at = :created_at
			END @IF
			
		LIMIT 1
	END
	
	-- add product_content_revision

	CREATE PROCEDURE add(
		IN revision ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_content_revision_data  = @FILTER(:revision, product_content_revision)
		
		
		INSERT INTO product_content_revision 
			
			( @KEYS(:product_content_revision_data) )
			
	  	VALUES ( :product_content_revision_data );

	END
	
	-- edit product content revision

	CREATE PROCEDURE edit(
		IN revision ROW,
		IN product_id INT,
		IN language_id INT,
		IN admin_id INT,
		IN created_at INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:revision, product_content_revision)

		@IF !empty(:revision)
		THEN
		UPDATE product_content_revision 
			
			SET @LIST(:revision) 
			
		 WHERE 
			product_id = :product_id AND
			language_id = :language_id AND
			created_at = :created_at

			@IF !empty(:admin_id) 
			THEN			
				AND admin_id = :admin_id
			END @IF
		END @IF;	

	END
	
	-- delete product content revision

	CREATE PROCEDURE delete(
		IN product_id INT,
		IN language_id INT,
		IN admin_id INT,
		IN created_at CHAR,
		OUT affected_rows
	)
	BEGIN
		-- product_content_revision
		DELETE FROM product_content_revision  WHERE 
			product_id = :product_id AND
			language_id = :language_id AND
			created_at = :created_at 
			
			@IF !empty(:admin_id) 
			THEN			
				AND admin_id = :admin_id
			END @IF;				
	END
