-- post_content_meta

	-- get one post_content_meta

	CREATE PROCEDURE get(
		IN post_id INT,
		IN namespace CHAR,
		IN key CHAR,
		
		OUT fetch_one,
	)
	BEGIN

		SELECT value
			FROM post_content_meta AS _
		WHERE _.`key` = :key 
		
		@IF !empty(:post_id) 
		THEN 
			AND _.post_id = :post_id
		END @IF		

		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		
		;
		
	END
    
	CREATE PROCEDURE set(
		IN post_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO post_content_meta
            (post_id, namespace, `key`, value )
        
        VALUES (:post_id, :namespace, :key, :value )
        
        ON DUPLICATE KEY 
            UPDATE value = values(value);
		
	END


    CREATE PROCEDURE getMulti(
		IN post_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		OUT fetch_all
	)
	BEGIN

		SELECT post_id, namespace, `key`, value
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
			AND _.`key`IN (:key)
		END @IF	
;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN post_id INT,
		IN namespace CHAR,
		IN meta ARRAY
	)
	BEGIN

        INSERT INTO post_content_meta
            (post_id, namespace, `key`, value)

            VALUES (:post_id, :namespace, :each)

        ON DUPLICATE KEY 
            UPDATE value = VALUES(value);
		
	END
    
	CREATE PROCEDURE delete(
		IN post_id INT,
		IN namespace CHAR,
		IN key ARRAY
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
			AND `key` IN (:key)
		END @IF	

		@IF !empty(:post_id) 
		THEN 
			AND post_id = :post_id
		END @IF	
		;		
		
	END    
