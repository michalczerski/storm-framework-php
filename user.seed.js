const db = connect( 'mongodb://localhost/blog');

db.users.insertOne({
    name: "Admin",
    password: "c1c224b03cd9bc7b6a86d77f5dace40191766c485cd55dc48caf9ac873335d6f"});