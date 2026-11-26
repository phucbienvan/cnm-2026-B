from fastapi import FastAPI

app = FastAPI(title="CNM FastAPI")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


from app.routes import router


@app.get("/users")
def get_user() -> dict[str, str]:
    return {"name": "phuc bv", "age": "28", "sex": "boy"}


@app.post("/users")
def create_user() -> dict[str, str]:
    return {"new_name": "phuc bv", "new_age": "28", "new_sex": "boy"}
@app.put("/users")
def update_user() -> dict[str, str]:
    return {
        "message": "update user successful",
    }


@app.delete("/{id}")
def delete_user(id) -> dict[str, str]:
    return {
        "message": f"deleted user {id}",
    }


app = FastAPI(title="CNM FastAPI")
app.include_router(router)