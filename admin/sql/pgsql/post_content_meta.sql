-- post_content_meta

	-- get one post_content_meta

	CREATE PROCEDURE get(
		IN post_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN language_id INT,
		
		OUT fetch_one
	)
	BEGIN

            SELECT value
            FROM post_content_meta AS _
		WHERE 1 = 1
		
		@IF !empty(:post_id) 
		THEN 
			AND _.post_id = :post_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		
		
		@IF !empty(:key) 
		THEN 
			AND  _."key" = :key
		END @IF		

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF		
		;
		
	END
    
	CREATE PROCEDURE set(
		IN post_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
        IN language_id INT,
		
		OUT insert_id
	)
	BEGIN

            INSERT INTO post_content_meta
		(post_id, language_id, namespace, "key", value )

		VALUES (:post_id, :language_id, :namespace, :key , :value )
			
		ON CONFLICT("post_id", "language_id","namespace", "key") DO UPDATE SET "value" = :value;
		
	END

	CREATE PROCEDURE getMulti(
		IN post_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT,
		
		OUT fetch_all
	)
	BEGIN

            SELECT post_id, namespace, "key", value, language_id
			FROM post_content_meta AS _
		WHERE 1 = 1
		
		@IF !empty(:post_id) 
		THEN 
			AND _.post_id = :post_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _."key" IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN post_id INT,
		IN meta ARRAY,
		
		OUT insert_id
	)
	BEGIN
	
	   :meta = @FILTER(:meta, post_content_meta, false, true)
	
		@EACH(:meta) 
			INSERT INTO post_content_meta 
		
				( @KEYS(:each), post_id)
			
			VALUES ( :each, :post_id )   
			
			ON CONFLICT("post_id", "language_id","namespace", "key") DO UPDATE SET "value" = :each.value;
		
	END
    
	CREATE PROCEDURE delete(
		IN post_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT
	)
	BEGIN

        	DELETE FROM 
			post_content_meta 
        	WHERE 1 = 1
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND "key" IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND language_id = :language_id
		END @IF	
		
		@IF !empty(:post_id) 
		THEN 
			AND post_id = :post_id
		END @IF	
		;		
	END    
