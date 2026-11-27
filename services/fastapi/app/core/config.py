from dotenv import load_dotenv
import os

load_dotenv()

class Setting:
    DATABASE_URL : str = os.getenv("DATABASE_URL")

    @property
    def embedding_dimensions(self) -> int:
        return int(os.getenv("EMBEDDING_DIMENSIONS", "1536"))

settings = Setting()
