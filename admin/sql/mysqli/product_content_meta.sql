-- product_content_meta

	-- get one product_content_meta

	CREATE PROCEDURE get(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN language_id INT,
		
		OUT fetch_one,
	)
	BEGIN

		SELECT value
			FROM product_content_meta AS _
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		

		@IF !empty(:key) 
		THEN 
			AND _.`key` = :key
		END @IF		

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF		
		;
		
	END
    
	CREATE PROCEDURE set(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		IN language_id INT,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO product_content_meta
            (product_id, namespace, `key`, value, language_id)
        
        VALUES (:product_id, :namespace, :key, :value, :language_id )
        
        ON DUPLICATE KEY 
            UPDATE value = values(value);
		
	END

    CREATE PROCEDURE getMulti(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT,
		
		OUT fetch_all
	)
	BEGIN

		SELECT product_id, namespace, `key`, value, language_id
			FROM product_content_meta AS _
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _.`key` IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN product_id INT,
		IN meta ARRAY,
		
		OUT insert_id
	)
	BEGIN
	
	   :meta = @FILTER(:meta, product_content_meta, false, true)
	
		@EACH(:meta) 
			INSERT INTO product_content_meta 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id )   
			ON DUPLICATE KEY 
            UPDATE value = VALUES(value);
		
	END
    
	CREATE PROCEDURE delete(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT
	)
	BEGIN

        	DELETE FROM 
			product_content_meta 
        	WHERE 1 = 1
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND `key` IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND language_id = :language_id
		END @IF	
		
		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		;		
	END    
