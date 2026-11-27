from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from app.core.config import settings

engine = create_engine(settings.DATABASE_URL)
LocalSession = sessionmaker(bind=engine, autoflush=False, autocommit=False)

def get_db():
    db = LocalSession()
    try:
        yield db
    finally:
        db.close()
