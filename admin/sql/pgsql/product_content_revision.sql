-- Product revisions

	-- get all product_content_revisions

	PROCEDURE getAll(
		IN product_id INT,
		IN language_id INT,
		IN created_at INT,
		IN content INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- product_content_revision
		SELECT revision.product_id, revision.language_id, revision.created_at, revision.admin_id, admin.display_name, admin.username

			@IF !empty(:content) 
			THEN			
				,content
			END @IF
		
			FROM product_content_revision AS revision
			LEFT JOIN admin ON (admin.admin_id = revision.admin_id)
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN			
			AND revision.product_id = :product_id
		END @IF		
				
		@IF !empty(:language_id) 
		THEN			
			AND revision.language_id = :language_id
		END @IF	
					
		@IF !empty(:created_at) 
		THEN			
			AND revision.created_at = :created_at
		END @IF				
		
		
		ORDER BY revision.created_at DESC
		
		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(revision.product_id, product_content_revision) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product content revision

	PROCEDURE get(
		IN product_id INT,
		IN language_id INT,
		IN created_at CHAR,
		OUT fetch_row, 
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
			
			@IF !empty(:created_at) 
			THEN			
				AND _.created_at = :created_at
			END @IF
			
		LIMIT 1
	END
	
	-- add product_content_revision

	PROCEDURE add(
		IN revision ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_content_revision_data  = @FILTER(:revision, product_content_revision);
		
		
		INSERT INTO product_content_revision 
			
			( @KEYS(:product_content_revision_data) )
			
	  	VALUES ( :product_content_revision_data );

	END
	
	-- edit product content revision

	CREATE PROCEDURE edit(
		IN revision ARRAY,
		IN product_id INT,
		IN language_id INT,
		IN created_at INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:revision, product_content_revision);

		UPDATE product_content_revision 
			
			SET @LIST(:revision) 
			
		 WHERE 
			product_id = :product_id AND
			language_id = :language_id AND
			created_at = :created_at; 


	END
	
	-- delete product content revision

	PROCEDURE delete(
		IN product_id INT,
		IN language_id INT,
		IN created_at CHAR,
		OUT affected_rows
	)
	BEGIN
		-- product_content_revision
		DELETE FROM product_content_revision  WHERE 
			product_id = :product_id AND
			language_id = :language_id AND
			created_at = :created_at 
	END
