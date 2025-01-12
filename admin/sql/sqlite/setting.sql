-- Settings

	-- get one setting

	CREATE PROCEDURE get(
		IN site_id INT,
		IN namespace CHAR,
		IN key CHAR,
		
		OUT fetch_one,
	)
	BEGIN

		SELECT value
            FROM setting AS _
		WHERE _.`key` = :key 
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		
		
		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF		
		;
		
	END
    
	CREATE PROCEDURE set(
		IN site_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		
		OUT insert_id
	)
	BEGIN

            INSERT INTO setting
            (site_id, namespace, `key`, value)
        
            VALUES ( :site_id, :namespace, :key, :value )
        
		ON CONFLICT(`site_id`, `namespace`, `key`) DO UPDATE SET `value` = :value;
		
	END
    

    CREATE PROCEDURE getMulti(
		IN site_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		OUT fetch_all
	)
	BEGIN

		SELECT namespace, `key`, value
			FROM setting AS _
		WHERE 1 = 1


		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF	
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _.`key` IN (:key)
		END @IF	
		;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN site_id INT,
		IN namespace CHAR,
		IN settings ARRAY,
		
		OUT insert_id
	)
	BEGIN

	   -- :settings = @FILTER(:settings, setting, false, true)
	
		@EACH(:settings) 
			INSERT INTO setting 
		
				( site_id, namespace, `key`, value )
			
			VALUES ( :site_id, :namespace, :each_key, :each )  

		ON CONFLICT("site_id", "namespace", "key") DO UPDATE SET "value" = :each;
		
	END
    
	CREATE PROCEDURE delete(
		IN site_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM 
			setting 
		WHERE 1 = 1  
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND `key` IN (:key)
		END @IF	

		@IF !empty(:site_id) 
		THEN 
			AND site_id = :site_id
		END @IF	
		;		
		
	END 
