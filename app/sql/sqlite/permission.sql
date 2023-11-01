-- Roles

	-- get all roles 

	CREATE PROCEDURE hasAccess(
		IN key INT,
		IN group INT,
        IN role_id INT
	)
	BEGIN

		SELECT  *
                FROM permissions as p
            LEFT JOIN permission_role pr 
                ON (p.i = pr.permission_id AND pd.language_id = :language_id AND pr.role_id = :role_id)  
            
	END