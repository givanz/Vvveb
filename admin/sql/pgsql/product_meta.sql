-- product_content_meta

	-- get one product_content_meta

	CREATE PROCEDURE get(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		
		OUT fetch_one,
	)
	BEGIN

            SELECT value
            FROM product_content_meta AS _
		WHERE _."key" = :key 
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF	

		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	

		
	END
    
	CREATE PROCEDURE set(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO product_content_meta
            (product_id, namespace, "key", value )
        
        VALUES (:product_id, :namespace, :key, :value )
        
        ON DUPLICATE KEY 
            UPDATE value = values(value);
		
	END


    CREATE PROCEDURE getMulti(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		OUT fetch_all
	)
	BEGIN

		SELECT product_id, namespace, "key", value
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
			AND _."key" IN (:key)
		END @IF	
;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN product_id INT,
		IN namespace CHAR,
		IN meta ARRAY
	)
	BEGIN

        INSERT INTO product_content_meta
            (product_id, namespace, "key", value)

			VALUES (:product_id, :namespace, :meta)

		ON CONFLICT("product_id","namespace", "key") DO UPDATE SET "value" = :product_content_metas.value;
		
	END
    
	CREATE PROCEDURE delete(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY
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
			AND "key" IN (:key)
		END @IF	

		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		;		
		
	END    
